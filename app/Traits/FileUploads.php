<?php

namespace App\Traits;

use Illuminate\Support\Facades\Storage;

trait FileUploads
{

    public $categories_path = 'uploads/categories/';
    public $specifications_path = 'uploads/specifications/';
    public $products_path = 'uploads/products/';

  public function uploadFile($request, $path, $inputName)
  {

    // get file extenstion
    $fileExt = $request->file($inputName)->getClientOriginalExtension();
    // $fileName = pathinfo($request->file($inputName)->getClientOriginalName())['filename'];
    // rename the filename
    $fileName = time() . '-' . rand(0, 1000) . '.' . $fileExt;
    // move the file to path the you are passed it into the argument on this fn..
    $request->file($inputName)->move($path, $fileName);
    // retrun the stored file with path !
    $storedFileName = $path . $fileName;
    return $storedFileName;
  }

  public function uploadFiles($file, $path) {
    // get file extenstion
    // $fileName = pathinfo($file->getClientOriginalName())['filename'];
    $fileExt = $file->getClientOriginalExtension();
    // rename the filename
    $fileName = time() . '-' . rand(0, 1000) . '.' . $fileExt;

    // move the file to path the you are passed it into the argument on this fn..
    $file->move($path, $fileName);
    // retrun the stored file with path !
    $storedFileName = $path . $fileName;
    return $storedFileName;
  }


  public function rename($request,$model, $name) {
      // array of folders of image file
      $arrayOfFoldersAndFiles = explode('/', $model->image);
      $arrayOfFoldersOnly = explode('/', dirname($model->image));
      $index = array_search($model->name_en, $arrayOfFoldersAndFiles);
      if($index) {
          $arrayOfFoldersAndFiles[$index] = $request->name_en;
          $arrayOfFoldersOnly[$index] = $request->name_en;
          // rename the directory name of the image
          rename(dirname($model->image), implode('/',$arrayOfFoldersOnly));
          $model->update([
            $name => implode('/',$arrayOfFoldersAndFiles)
          ]);
      }
  }
    public function UserFileUpload($file,$folder) // Taking input image as parameter
    {

        $fileName= $file->getClientOriginalName();

        $pathFile = Storage::disk('uploads')->putFileAs(($folder ?? ''), $file, $fileName);
        return $pathFile;
//$file_name = str_random(20);
//$ext = strtolower($file_name->getClientOriginalExtension()); // You can use also getClientOriginalName()
//$file_full_name = $file_name.'.'.$ext;
//$upload_path = 'image/';    //Creating Sub directory in Public folder to put image
//$file_url = $folder.$file_name;

        //$file->store('toPath', ['disk' => public_path("data\".$folder)]);
//$success = $file_name->move($upload_path,$file_name);

        // Just return file
    }
}
