<?php

namespace App\Http\Controllers\Api\Client\Web\Admin\Blog;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class AdminBlogController
{
    public function create(Request $request) {
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
 $validator = Validator::make($request->all(), [
        'title' => 'required|string',
        'category' => 'required|integer',
        'tags' => 'array',
        'slug' => 'required|string',
        'data' => 'required|string',
        'pictures.*' => 'image|mimes:webp,jpg,jpeg,gif,bmp,tiff,ico|max:2048', // Règles de validation pour les images
    ]);
          if ($validator->fails()) {
        return response()->json(['status' => 'error', 'message' => $validator->errors()], 400);
    }
            $blog = new Blog;
            $blog->user_id = $user->id;
            $blog->title = $request->title;
            $blog->category_id = $request->category;
            $blog->tags = json_encode($request->tags);
            $blog->slug = $request->slug;
            $blog->content = $request->data;
             $pictures = [];
    if ($request->hasFile('pictures')) {
       foreach ($request->file('pictures') as $file) {
        $path = $file->store('public/images');
        $url = Storage::url($path);

        // Vérifier si le fichier est une image et n'est pas déjà au format WebP
        if ($file->getClientOriginalExtension() !== 'webp') {
            // Ouvrir l'image avec Intervention Image
            $image = Image::make($file);

            // Convertir l'image en format WebP
            $image->encode('webp', 80); // Utilisez la qualité de compression souhaitée

            // Générer un nouveau nom de fichier avec l'extension .webp
            $newFileName = pathinfo($this->generateUniqueFileName($request->title), PATHINFO_FILENAME) . '.webp';
            if (!Storage::exists($path)) {
                Storage::makeDirectory('public/images/' . $request->title . '/');
            }
            // Enregistrer l'image convertie
            Storage::put('public/images/' . $request->title . '/' . $newFileName, $image->stream());

            // Mettre à jour l'URL avec le nouveau nom de fichier
            $url = Storage::url('public/images/' . $request->title . '/' . $newFileName);
        }

        $pictures[] = $url;
    }
    }
            $blog->pictures = json_encode($pictures);

            $blog->save();

            return response()->json(['status' => 'success', 'message' => 'Blog created successfully'], 201);

    }
    public function remove(Blog $blog) {
         $user = auth('sanctum')->user();

        if (!$user || $user->role !== 1) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }
        $blog->delete();
        return response()->json(['status' => 'success', 'message' => 'Blog removed successfully'], 201);
    }
    public function edit(Request $request, Blog $blog)
{
    $user = auth('sanctum')->user();

    if (!$user || $user->role !== 1) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
        'title' => 'required|string',
        'category' => 'required|integer',
        'tags' => 'array',
        'slug' => 'required|string',
        'data' => 'required|string',
        'pictures.*' => 'image|mimes:webp,jpg,jpeg,gif,bmp,tiff,ico|max:2048', // Règles de validation pour les images
    ]);

    if ($validator->fails()) {
        return response()->json(['status' => 'error',     'message' => $validator->errors()->first()], 400);
    }

    // Vérifier si l'utilisateur est le propriétaire du blog
    if ($blog->user_id !== $user->id) {
        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }

    $blog->title = $request->title;
    $blog->category_id = $request->category;
    $blog->tags = $request->tags;
    $blog->slug = $request->slug;
    $blog->content = $request->data;

    // Enregistrement des nouvelles images
    $pictures = [];
    if ($request->hasFile('pictures')) {
       foreach ($request->file('pictures') as $file) {
        $path = $file->store('public/images');
        $url = Storage::url($path);

        // Vérifier si le fichier est une image et n'est pas déjà au format WebP
        if ($file->getClientOriginalExtension() !== 'webp') {
            // Ouvrir l'image avec Intervention Image
            $image = Image::make($file);

            // Convertir l'image en format WebP
            $image->encode('webp', 80); // Utilisez la qualité de compression souhaitée

            // Générer un nouveau nom de fichier avec l'extension .webp
            $newFileName = pathinfo($this->generateUniqueFileName($request->title), PATHINFO_FILENAME) . '.webp';
            if (!Storage::exists($path)) {
                Storage::makeDirectory('public/images/' . $request->title . '/');
            }
            // Enregistrer l'image convertie
            Storage::put('public/images/' . $request->title . '/' . $newFileName, $image->stream());

            // Mettre à jour l'URL avec le nouveau nom de fichier
            $url = Storage::url('public/images/' . $request->title . '/' . $newFileName);
        }

        $pictures[] = $url;
    }
    }

    // Suppression des images existantes
    if ($request->has('remove_pictures')) {
        foreach ($request->remove_pictures as $picture) {
            Storage::delete(str_replace('/storage', 'public', $picture));
        }
    }

    // Fusionner les nouvelles images avec les images existantes
    $mergedPictures = array_merge(json_decode($blog->pictures, true), $pictures);
    $blog->pictures = json_encode($mergedPictures);

    $blog->save();

    return response()->json(['status' => 'success', 'message' => 'Blog updated successfully'], 200);
}
function generateUniqueFileName($title)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $length = 64;
    $randomString = '';

    // Générer la suite aléatoire de caractères
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }

    // Nom de fichier complet avec le répertoire et l'extension
    $fileName = 'public/images/' . $title . '/' . $randomString . '.webp';

    // Vérifier si le fichier existe déjà
    if (file_exists($fileName)) {
        // Générer un nouveau nom de fichier unique récursivement
        return generateUniqueFileName($title);
    }

    // Retourner le nom de fichier unique
    return $randomString;
}
}