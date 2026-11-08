<?php
defined('ABSPATH') || exit;

function obra_default_content($lang) {
    $catalog = [
        'es'=>['Muy pronto estaremos aquí','Estamos preparando nuestra web. Déjanos tu correo y te avisaremos cuando esté lista.','ESTAMOS PREPARANDO ALGO NUEVO','Avísame','Muy pronto estaremos aquí','Nuestro sitio estará disponible muy pronto.'],
        'en'=>["We’ll be here soon",'We’re getting our website ready. Leave your email and we’ll let you know when it’s live.','SOMETHING NEW IS COMING','Notify me',"We’ll be here soon",'Our website will be available soon.'],
        'fr'=>['Nous serons bientôt là','Nous préparons notre site. Laissez votre adresse e-mail et nous vous préviendrons dès son ouverture.','QUELQUE CHOSE DE NOUVEAU ARRIVE','Prévenez-moi','Nous serons bientôt là','Notre site sera bientôt disponible.'],
        'de'=>['Bald sind wir für Sie da','Wir bereiten unsere Website vor. Hinterlassen Sie Ihre E-Mail-Adresse und wir informieren Sie zum Start.','ETWAS NEUES ENTSTEHT','Benachrichtigen','Bald sind wir für Sie da','Unsere Website ist bald verfügbar.'],
        'it'=>['Presto saremo qui','Stiamo preparando il nostro sito. Lascia la tua email e ti avviseremo quando sarà pronto.','STA ARRIVANDO QUALCOSA DI NUOVO','Avvisami','Presto saremo qui','Il nostro sito sarà presto disponibile.'],
        'pt'=>['Em breve estaremos aqui','Estamos a preparar o nosso site. Deixe o seu email e avisaremos quando estiver pronto.','ALGO NOVO ESTÁ A CHEGAR','Avise-me','Em breve estaremos aqui','O nosso site estará disponível em breve.'],
    ];
    $placeholders=[
        'es'=>['Tu correo','Tu nombre','Tu teléfono'],
        'en'=>['Your email','Your name','Your phone number'],
        'fr'=>['Votre adresse e-mail','Votre nom','Votre téléphone'],
        'de'=>['Ihre E-Mail-Adresse','Ihr Name','Ihre Telefonnummer'],
        'it'=>['La tua email','Il tuo nome','Il tuo telefono'],
        'pt'=>['O seu email','O seu nome','O seu telefone'],
    ];
    return array_combine(['email_placeholder','name_placeholder','phone_placeholder'],$placeholders[$lang]??$placeholders['en']) + array_combine(['title','text','eyebrow','button_text','seo_title','seo_description'],$catalog[$lang]??$catalog['en']);
}

function obra_content_is_default($key,$value) {
    foreach(['es','en','fr','de','it','pt'] as $lang)
        if($value===(obra_default_content($lang)[$key]??null))return true;
    $legacy=[
        'title'=>['Muy pronto, una nueva forma de verlo todo.','Algo nuevo está por llegar.','Something wonderful is coming.','Une nouvelle aventure commence.','Etwas Neues entsteht.','Qualcosa di nuovo sta arrivando.','Algo novo está a chegar.'],
        'text'=>['We’re getting our website ready. Leave your phone number and we’ll let you know when it’s live.','Estamos preparando nuestra web. Déjanos tu teléfono y te avisaremos cuando esté lista.','Nous préparons notre site. Laissez votre numéro de téléphone et nous vous préviendrons dès son ouverture.','Wir bereiten unsere Website vor. Hinterlassen Sie Ihre Telefonnummer und wir informieren Sie zum Start.','Stiamo preparando il nostro sito. Lascia il tuo numero di telefono e ti avviseremo quando sarà pronto.','Estamos a preparar o nosso site. Deixe o seu telefone e avisaremos quando estiver pronto.','Estamos preparando nuestra web. Muy pronto estará disponible.','We’re getting our website ready. It will be available soon.','Nous préparons notre site. Il sera bientôt disponible.','Wir bereiten unsere Website vor. Sie ist bald verfügbar.','Stiamo preparando il nostro sito. Sarà presto disponibile.','Estamos a preparar o nosso site. Estará disponível em breve.','Estamos dando los últimos retoques. Déjanos tu correo y te avisaremos cuando abramos las puertas.','Estamos dando los últimos retoques. Déjanos tu correo y te avisaremos cuando abramos las puertas','Estamos preparando nuestra nueva web. Te avisaremos cuando esté lista.','We are putting the finishing touches on our new website. Be the first to know.','Notre nouveau site arrive bientôt. Soyez les premiers informés.','Unsere neue Website ist bald verfügbar. Bleiben Sie informiert.','Il nostro nuovo sito sarà presto online. Resta aggiornato.','Estamos a preparar a nossa nova web. Seja o primeiro a saber.'],
        'eyebrow'=>['ESTAMOS CONSTRUYENDO ALGO ESPECIAL','MUY PRONTO','A NEW CHAPTER','BIENTÔT','BALD VERFÜGBAR','PROSSIMAMENTE','EM BREVE'],
        'seo_title'=>['Estamos preparando algo nuevo'],
    ];
    return in_array($value,$legacy[$key]??[],true);
}

function obra_localize_content($settings) {
    $lang=obra_language($settings);
    foreach(obra_default_content($lang) as $key=>$default){
        if(!empty($settings['custom_content'][$key]))continue;
        $value=$settings['translations'][$lang][$key]??$settings[$key]??$default;
        $settings[$key]=obra_content_is_default($key,$value)?obra_translate_public($default):$value;
    }
    if(empty($settings['custom_content']['text']) && (!$settings['show_form'] || (!$settings['form_email']&&!$settings['form_phone']))){
        $neutral=['es'=>'Estamos preparando nuestra web. Muy pronto estará disponible.','en'=>'We’re getting our website ready. It will be available soon.','fr'=>'Nous préparons notre site. Il sera bientôt disponible.','de'=>'Wir bereiten unsere Website vor. Sie ist bald verfügbar.','it'=>'Stiamo preparando il nostro sito. Sarà presto disponibile.','pt'=>'Estamos a preparar o nosso site. Estará disponível em breve.'];
        $settings['text']=obra_translate_public($neutral[$lang]??$neutral['en']);
    }
    if(empty($settings['custom_content']['text']) && $settings['show_form'] && $settings['form_phone'] && !$settings['form_email']) {
        $phone=['es'=>'Estamos preparando nuestra web. Déjanos tu teléfono y te avisaremos cuando esté lista.','en'=>'We’re getting our website ready. Leave your phone number and we’ll let you know when it’s live.','fr'=>'Nous préparons notre site. Laissez votre numéro de téléphone et nous vous préviendrons dès son ouverture.','de'=>'Wir bereiten unsere Website vor. Hinterlassen Sie Ihre Telefonnummer und wir informieren Sie zum Start.','it'=>'Stiamo preparando il nostro sito. Lascia il tuo numero di telefono e ti avviseremo quando sarà pronto.','pt'=>'Estamos a preparar o nosso site. Deixe o seu telefone e avisaremos quando estiver pronto.'];
        $settings['text']=obra_translate_public($phone[$lang]??$phone['en']);
    }
    return $settings;
}
