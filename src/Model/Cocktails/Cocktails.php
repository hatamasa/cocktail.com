<?php
namespace App\Model\Cocktails;

use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Common\ImgUploader;
use App\Model\Common\Logger;
use App\Model\Common\FileUploadException;

class Cocktails
{
    private $logger;

    private $params;

    private $errors = [];

    public function __construct($params = null){
        $this->logger = new Logger();
        $this->params = $params;
    }

    /**
     * カクテル検索時のバリデーションを実行する
     * @return array
     */
    public function validateForSearch(){

        // パラメータがない場合はエラー
        if(count($this->params) == 0){
            $this->errors[] = "検索条件を入力してください";
            return $this->errors;
        }

        if(isset($this->params['name'])){
            // nameの空文字のみの検索はエラーとする
            // 全角スペースはemptyと判断されないため半角に変換してtrimする
            if(empty(trim(mb_convert_kana($this->params['name'], 's'))) && count($this->params, 1) === 1) {
                $this->errors[] = "検索条件を入力してください";
                return $this->errors;
            }
            if(mb_strlen(trim($this->params['name'])) > 30){
                $this->errors[] = "カクテル名は30文字以内で入力してください。";
            }
        }

        return $this->errors;
    }

    /**
     * カクテル登録用のバリデーションを実行する
     */
    public function valudateForCreate()
    {
        $validator = new Validator();
        $validator
            ->allowEmpty('img')

            ->requirePresence('name', true, 'グラスの入力は必須です')
            ->notEmpty('name', '名前の入力は必須です')
            ->add('name', 'length', [
                'rule' => ['maxLength', 30],
                'message' => '名前は30文字以内で入力ください'])

            ->requirePresence('glass', true, 'グラスの入力は必須です')

            ->requirePresence('percentage', true, '強さの入力は必須です')

            ->allowEmpty('color')
            ->add('color', 'length', [
                'rule' => ['maxLength', 10],
                'message' => '色は10文字以内で入力ください'])

            ->requirePresence('taste', true, 'テイストの入力は必須です')

            ->allowEmpty('processes')
            ->add('processes', 'length', [
                    'rule' => ['maxLength', 250],
                    'message' => '作成手順は250文字以内で入力ください'])

            ->requirePresence('element_id_selected', true, '材料は少なくとも一つ以上入力してください')
                ;

         $imgValidater = new Validator();
         $imgValidater
             ->add('type', ['list' => [
                 'rule' => ['inList', ['image/jpg', 'image/jpeg', 'image/png', 'image/gif']],
                 'message' => 'jpg, png, gif のみアップロード可能です.',
             ]])
            ->add('size', 'comparison', [
                    'rule' => ['comparison', '<', 10485760],
                    'message' => '画像サイズは10Mまでです'
                ])
            ;

            $validator->addNested('img', $imgValidater);

        return $validator->errors($this->params);
    }

    /**
     * カクテル詳細を取得する
     * @param $id
     * @return array
     */
    public function fetchCocktailDetail($cocktail_id){
        $results = [];

        $cocktailsTable = TableRegistry::get('Cocktails');
        $results['cocktail'] = $cocktailsTable->findById($cocktail_id)->contain(['CocktailsTags', 'Tags'])->first();

        $cocktailsElementsTable = TableRegistry::get('CocktailsElements');
        $results['cocktails_elements'] = $cocktailsElementsTable->fetchElementsByCocktailId($cocktail_id);

        return $results;
    }

    /**
     * カクテルを登録する
     * @param $params
     */
    public function saveCocktail(){

        // S3にアップロードしてアップロード先のURLをセットする
        if(!empty($this->params['img']['name'])){
            $uploader = new ImgUploader($this->params);
            try{
                $img_url = $uploader->execute();
            } catch (FileUploadException $e) {
                $this->logger->log('[ERROR] uploaded image is failed img_url:[ ' . $img_url . ']', LOG_ERR);
            }
            $this->logger->log('uploaded image: ' . $img_url);
        }
        // カクテルの配列作成
        $data = [
            'id' => $this->params['id']??'',
            'name' => $this->params['name'],
            'search_name' => CocktailsUtil::tohalfString($this->params['name']),
            'glass' => $this->params['glass'],
            'percentage' => $this->params['percentage'],
            'color' => $this->params['color'],
            'taste' => $this->params['taste'],
            'processes' => $this->params['processes'],
            'img_url' => $img_url??null,
        ];
        // カクテル要素の配列作成
        for ($i = 0; $i < count($this->params['element_id_selected']); $i++){
            $data['cocktails_elements'][] = [
                'id' => $this->params['saved_id'][$i]??'',
                'cocktail_id' => $this->params['id'],
                'element_id' => $this->params['element_id_selected'][$i],
                'amount' => $this->params['amount_selected'][$i],
            ];
        }
        // カクテルタグの配列作成
        if(isset($this->params['tag_id'])){
            foreach ($this->params['tag_id'] as $tag_id){
                $data['cocktails_tags'][] = [
                    'id' => '',
                    'cocktail_id' => $this->params['id'],
                    'tag_id' => $tag_id,
                ];
            }
        }
        // エンティティとアソシエーションを作成
        $cocktailsTable = TableRegistry::get('Cocktails');
        $cocktailsElementsTable = TableRegistry::get('CocktailsElements');
        $cocktailsTagsTable = TableRegistry::get('CocktailsTags');

        $connection = ConnectionManager::get('default');
        $connection->begin();
        try{
            // patchEntityのみではアソシエーション削除の場合、削除されない
            // そのためCocktailsElements, CocktailsTagsを全削除して入れ直す
            $cocktailsElementsTable->deleteAll(['cocktail_id' => $this->params['id']]);
            $cocktailsTagsTable->deleteAll(['cocktail_id' => $this->params['id']]);

            $cocktail = $cocktailsTable->newEntity();
            $cocktail = $cocktailsTable->patchEntity($cocktail, $data, [
                'associated' => ['CocktailsElements', 'CocktailsTags'],
            ]);
            $result = $cocktailsTable->save($cocktail);
            $connection->commit();

        } catch (\Exception $e){

            $connection->rollback();
            throw new \Exception($e->getMessage());
        }

        return $result;
    }

    /**
     * IDからエレメントを取得して表示用リストを作成する
     * @return array $elements
     */
    public function makeElementsTableList(){
        $elementsRepository = TableRegistry::get('Elements');
        $elements_list = [];
        for ($i = 0; $i < count($this->params['element_id_selected']); $i++){
            $elements_list[$i] = $elementsRepository->findById($this->params['element_id_selected'][$i])->first();
            $elements_list[$i]['saved_id'] = $this->params['saved_id'][$i]??'';
            $elements_list[$i]['amount'] = $this->params['amount_selected'][$i];
        }
        return $elements_list;
    }

}