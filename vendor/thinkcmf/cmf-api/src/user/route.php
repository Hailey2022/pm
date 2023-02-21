<?php
use think\facade\Route;
Route::get('user/favorites/my', 'user/favorites/getFavorites'); //获取收藏列表
Route::get('user/comments/my', 'user/comments/getUserComments'); //获取我的评论列表
Route::get('user/comments', 'user/comments/getComments'); //获评论列表
Route::get('user/favorites/hasFavorite', 'user/favorites/hasFavorite'); 
Route::post('user/articles/deletes', 'user/Articles/deletes');
Route::post('user/favorites', 'user/favorites/setFavorites'); //添加收藏
Route::post('user/comments', 'user/comments/setComments');//添加评论
Route::delete('user/favorites/:id', 'user/favorites/unsetFavorites');  //删除收藏
Route::delete('user/comments/:id', 'user/comments/delComments'); //删除评论
