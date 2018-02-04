<?php
namespace App\Controller;

use App\Model\Cocktails\Cocktails;

/**
 * カクテルコントローラ
 * /cocktails
 * @author hatamasa
 */
class CocktailsController extends AppController
{

    /**
     * 初期表示
     * GET /
     */
    public function index()
    {
    }

    /**
     * カクテル検索
     * GET /search
     * @param　$param
     */
    public function search()
    {
        $results = [];
        $params = $this->request->getQueryParams();

        $cocktails = new Cocktails($params);
        $errors = $cocktails->validateForSearch();

        if (! $errors) {
            $start = microtime(true);
            $results = $this->Cocktails->fetchAllCocktails($params);
            $end = microtime(true);
            $this->logger->log("fetchAllCocktails time: " . ($end - $start), LOG_DEBUG);

            if (count($results) == 0) {
                $this->Flash->set("検索結果はありません");
            } else {
                $this->Flash->set(count($results) . "件ヒットしました");
            }
        } else {
            foreach ($errors as $error){
                $this->Flash->error($error);
            }
        }

        $this->set(compact('results', 'params'));
        $this->render('index');
    }

    /**
     * カクテル詳細表示
     * GET /:id
     * @param  $id
     */
    public function view($id)
    {
        $cocktails = new Cocktails();
        $results = $cocktails->fetchCocktailDetail($id);

        $this->set('cocktail', $results['cocktail']);
        $this->set('cocktail_tags', $results['cocktail']['CocktailTags']??[] );
        $this->set('cocktail_elements', $results['cocktail_elements']);
    }

    /**
     * カクテル編集
     * GET|PUT /:id/edit
     * @param  $id
     */
    public function edit($id)
    {
        $params = [];
        $cocktail_elements = [];
        $errors = [];

        if($this->request->is('GET')){
            // 画面表示。idから検索して登録画面を表示する
            $cocktails = new Cocktails();
            $results = $cocktails->fetchCocktailDetail($id);

            $params = $results['cocktail'];
            $cocktail_elements = $results['cocktail_elements'];

        } else if ($this->request->is('PUT')){

            $params = $this->request->getData();
            // 登録時処理
            $cocktails = new Cocktails($params);
            $errors = $cocktails->valudateForCreate();

            // バリデエラーがない場合、登録を行う
            if (! $errors) {
                try {
                    $cocktails->saveCocktail();
                    $this->Flash->success('保存しました');
                    // 登録完了した場合、詳細画面を表示する
                    $this->redirect('cocktails/' . $id);

                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage(), LOG_ERR);
                    $this->Flash->error('保存中にエラーが発生しました');
                }
            } else {
                $this->Flash->error('エラー項目があります');
            }

            // バリデエラー、登録エラーがある場合、かつ材料リストがある場合、入力保持のため材料テーブルを作成する
            if(isset($params['element_id_selected'])){
                $cocktail_elements = $cocktails->makeElementsTableList();
            }
        }

        // バリデエラー、Exception、画面表示の場合は再表示する
        $this->set(compact('params', 'cocktail_elements', 'errors'));
    }

    /**
     * カクテル作成
     *  GET|POST /add
     */
    public function add()
    {
        $params = [];
        $cocktail_elements = [];
        $errors = [];

        if($this->request->is('POST')){

            $params = $this->request->getData();
            // 登録時処理
            $cocktails = new Cocktails($params);
            $errors = $cocktails->valudateForCreate();

            // バリデエラーがない場合、登録を行う
            if (! $errors) {
                try {
                    $results = $cocktails->saveCocktail();
                    $this->Flash->success('保存しました');
                    // 登録完了した場合、詳細画面を表示する
                    $this->redirect('cocktails/' . $results['id']);

                } catch (\Exception $e) {
                    $this->logger->log($e->getMessage(), LOG_ERR);
                    $this->Flash->error('保存中にエラーが発生しました');
                }
            } else {
                $this->Flash->error('エラー項目があります');
            }

            // バリデエラー、登録エラーがある場合、かつ材料リストがある場合、入力保持のため材料テーブルを作成する
            if (isset($params['element_id_selected'])) {
                $cocktail_elements = $cocktails->makeElementsTableList();
            }
        }

        // バリデエラー、Exception、画面表示からの遷移の場合は登録画面を表示する
        $this->set(compact('params', 'cocktail_elements', 'errors'));
    }

    public function delete()
    {
    }

    /**
     * (Ajax用)エレメントのプルダウン制御用
     * GET /getElementsOptions/:id
     * @param $category_kbn
     */
    public function getElementsOptions($category_kbn)
    {
        if (!$this->request->is('ajax')) {
            $this->redirect('/');
        }

        $cocktails = new Cocktails();
        $elements_list = $cocktails->getElementsList($category_kbn);

        $this->set(compact('elements_list'));
        $this->render('/Element/Cocktails/ajax_elements_options','');
    }

    /**
     * (Ajax用)材料追加用
     * POST /mergeElementsTable
     */
    public function mergeElementsTable()
    {
        if (!$this->request->is('ajax')) {
            $this->redirect('/');
        }
        $params = $this->request->getData();

        // 材料リストに、追加される材料を追加
        $params['element_id_selected'][] = $params['element_id'];
        $params['amount_selected'][] = $params['amount'];

        $cocktails = new Cocktails($params);
        $cocktail_elements = $cocktails->makeElementsTableList();

        $this->set(compact('cocktail_elements'));
        $this->render('/Element/Cocktails/ajax_elements_table','');
    }

    /**
     * (Ajax用)材料削除用
     * POST /deleteElementsTable
     */
    public function deleteElementsTable(){

        if (!$this->request->is('ajax')) {
            $this->redirect('/');
        }
        $params = $this->request->getData();

        // 材料リストから、削除される材料を削除
        array_splice($params['saved_id'], $params['del_index'], 1);
        array_splice($params['element_id_selected'], $params['del_index'], 1);
        array_splice($params['amount_selected'], $params['del_index'], 1);

        $cocktails = new Cocktails($params);
        $cocktail_elements = $cocktails->makeElementsTableList();

        $this->set(compact('cocktail_elements'));
        $this->render('/Element/Cocktails/ajax_elements_table','');
    }

}