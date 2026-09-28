<?php

namespace app\models;
use Yii;
use yii\helpers\FileHelper;
use app\models\Book;
use app\models\Record;
use yii\helpers\StringHelper;
use yii\helpers\Url;

class HelperImg {
    /**
     * Возвращает относительный путь папки, где хранятся сканы, 
     * если папки не существует, то возвращает пустую строку или наиболее повторяющееся значение пути из записей
     *
     * @param Book $book
     * @param boolean $returnNoExistedPath
     * @return array{path: string, existed: bool}
     */
    public static function getImagesFilepath(Book $book, bool $returnNoExistedPath = false): array {
        $filenames = Record::find()->select('filename')->
            andWhere(['book_id' => $book->id])->orderBy('filename')->column();

        if(!$filenames)
            return ['path' => '', 'existed' => false];

        $oldDirpath = [];

        foreach($filenames as $filename){
            if(!$filename)
                continue;
    
            $dirpath = FileHelper::normalizePath(Yii::getAlias("@images/" . $filename));

            if(is_file($dirpath) || str_contains(basename($dirpath), '.')){
                $dirpath = StringHelper::dirname($dirpath);
                $relativePath = StringHelper::dirname($filename);
            }
            else
                $relativePath = $filename;

            if (!$relativePath)
                continue;

            if(!is_dir($dirpath)){
                if($returnNoExistedPath){
                    $oldDirpath[$relativePath] = ($oldDirpath[$relativePath] ?? 0) + 1;
                }

                continue;
            }
            else
                return [ 'path' => $relativePath, 'existed' => true ];
        }

        $maxKey = !empty($oldDirpath) ? array_search(max($oldDirpath), $oldDirpath) : '';
        if($maxKey === false) $maxKey = '';

        return [ 'path' => $maxKey, 'existed' => false];
    }

    /**
     * Возвращает путь титульника книги
     * @param \app\models\Book $book
     * @return string
     */
    public static function getTitleImage(Book $book): string {
        $record = Record::find()->andWhere(['book_id' => $book->id])->orderBy('id')->one();
        $value = self::getImagesFilepath($book);

        if(!$value['existed'])
            return '';

        $dirpath = FileHelper::normalizePath(Yii::getAlias("@images/" .  $value['path']));
        $files = FileHelper::findFiles($dirpath, [
            'recursive' => false,]);

        natcasesort($files);
        $files = array_values($files);

        if(!$files)
            return '';

        $filePath = StringHelper::dirname($record->filename) . '\\' . StringHelper::basename($files[0]);
        return Url::to(['image-viewer/index', 'path' => str_replace('\\', '/', $filePath)]);
    }

    /**
     * @return list<array<string, array<string, string>|string>>
     * @param \app\models\Book $book
     */
    public static function getImages(Book $book): array {
        $dirpath = self::getImagesFilepath($book);

        if(!$dirpath['existed'])
            return [];

        $canonicalPath = FileHelper::normalizePath(Yii::getAlias("@images") . '/' . $dirpath['path']);

        $files = FileHelper::findFiles($canonicalPath, [
            'recursive' => false,]);

        natcasesort($files);
        $files = array_values($files);
        $files = array_map('basename', $files);

        $result = [];
        $webPath = Url::to([
            '/image-viewer/image',
            'filename' => str_replace('\\', '/', $dirpath['path']),
        ]);

        foreach ($files as $file) {
            $upath = "$webPath/$file";
            
            $result[] = [
                'url' => str_replace(' ', '%20', $upath),
                'src2' => $dirpath['path'] . '\\' . $file,
                'src3' => $file,
            ];
        }

        return $result;
    }
}
