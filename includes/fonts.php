<?php
defined('ABSPATH') || exit;

/** Available theme presets, installed Font Library families and browser fallbacks. */
function obra_fonts() {
    $fonts = [];
    foreach ([
        'system'=>[obra_t('Sistema / Sans serif','System / Sans serif'),'system-ui, sans-serif'],
        'geometric'=>['Geometric Sans','"Avenir Next", Futura, sans-serif'],
        'serif'=>['Serif / Georgia','Georgia, serif'],
        'mono'=>[obra_t('Monoespaciada','Monospace'),'ui-monospace, monospace'],
        'arial'=>['Arial','Arial, Helvetica, sans-serif'],
        'verdana'=>['Verdana','Verdana, sans-serif'],
        'tahoma'=>['Tahoma','Tahoma, sans-serif'],
        'trebuchet'=>['Trebuchet MS','"Trebuchet MS", sans-serif'],
        'times'=>['Times New Roman','"Times New Roman", serif'],
        'courier'=>['Courier New','"Courier New", monospace'],
        'cursive'=>[obra_t('Cursiva','Cursive'),'cursive'],
        'fantasy'=>[obra_t('Fantasía','Fantasy'),'fantasy'],
    ] as $id=>$font) $fonts[$id]=['id'=>$id,'name'=>$font[0],'family'=>$font[1],'source'=>obra_t('Navegador','Browser'),'faces'=>[]];
    $add=function($font,$source) use (&$fonts) {
        if(empty($font['fontFamily']))return;
        $family=$font['fontFamily'];
        // A CSS family list, never an arbitrary CSS declaration.
        if(preg_match('/[;{}<>\\\\]/',$family))return;
        $id='wp-'.substr(md5($family),0,16);
        $fonts[$id]=['id'=>$id,'name'=>$font['name']??trim($family,'"\''),'family'=>$family,'source'=>$source,'faces'=>$font['fontFace']??[]];
    };
    $walk=function($items) use (&$walk,$add) {
        foreach((array)$items as $item) {
            if(!is_array($item))continue;
            if(isset($item['fontFamily']))$add($item,'WordPress');
            else $walk($item);
        }
    };
    if(function_exists('wp_get_global_settings'))$walk(wp_get_global_settings(['typography','fontFamilies']));
    foreach(get_posts(['post_type'=>'wp_font_family','post_status'=>'publish','numberposts'=>-1]) as $post){
        $font=json_decode($post->post_content,true);
        if(!is_array($font))continue;
        $font['fontFace']=[];
        foreach(get_posts(['post_type'=>'wp_font_face','post_parent'=>$post->ID,'post_status'=>'publish','numberposts'=>-1]) as $face){
            $data=json_decode($face->post_content,true);
            if(is_array($data))$font['fontFace'][]=$data;
        }
        $add($font,obra_t('Biblioteca de WordPress','WordPress Font Library'));
    }
    return $fonts;
}

function obra_print_fonts($selected) {
    if(!function_exists('wp_print_font_faces'))return;
    // Core resolves theme-relative file: URLs and font variations.
    wp_print_font_faces();
    $font=obra_fonts()[$selected]??null;
    if(empty($font['faces']))return;
    $faces=[];
    foreach($font['faces'] as $face){
        $converted=[];
        foreach($face as $key=>$value){
            $css=strtolower(preg_replace('/([a-z])([A-Z])/','$1-$2',$key));
            $converted[$css]=$value;
        }
        // Theme faces are already printed by core. Library faces have resolved URLs.
        if(strpos(wp_json_encode($converted['src']??''),'file:')!==false)continue;
        $faces[]=$converted;
    }
    if($faces)wp_print_font_faces([$faces]);
}
