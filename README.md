# Yii export CDataProvider to CSV

Example:

```php
public function actionExport()
{
    $dataProvider = SomeModel::model()->search(); // instance of IDataProvider (CActiveDataProvder, CArrayDataProvider, etc)
    $columns = [
        // Column name
        'id',
        // This is a callback example, to able modify column value
        'name' => function (DataProviderCsvExport $gridExport, $item) {
            return $item->name; 
        },
        // Related column example with callback
        'profile.skype' => function (DataProviderCsvExport $gridExport, $item) {
            return $item->profile !== null ? $item->profil->skype : '-';
        },
    ];
    $columnHeaders = [
        $dataProvider->model->getAttributeLabel('id'),
        $dataProvider->model->getAttributeLabel('patient.fullname'),
        $dataProvider->model->getAttributeLabel('name'),
        $dataProvider->model->getAttributeLabel('profile.skype'),
    ];

    try {
        (new DataProviderCsvExport($dataProvider))
            ->setFilename('custom_filename_{date}.csv')
            ->setColumnHeaders($columnHeaders)
            ->setColumns($columns)
            ->run();
    } catch (\LogicException $e) {
        Yii::app()->user->setFlash('exportError', Yii::t('app', $e->getMessage()));
        $this->redirect(['index']);
    }
}
```
