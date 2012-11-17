<?php
class Abs2Rel extends MTPlugin {
    var $app;
    var $registry = array(
        'name' => 'Abs2Rel',
        'id'   => 'Abs2Rel',
        'key'  => 'abs2rel',
        'author_name' => 'Alfasado Inc.',
        'author_link' => 'http://alfasado.net/',
        'version' => '0.1',
        'description' => 'Convert absolute path to relative path.',
        'config_settings' => array(
            'DynamicAbs2Rel' => array( 'default' => 0 ),
        ),
        'callbacks' => array(
            'build_page' => 'filter_build_page',
        ),
    );

    function filter_build_page ( $mt, &$ctx, &$args, &$content ) {
        $app = $ctx->stash( 'bootstrapper' );
        $plugin = $app->component('Abs2Rel');
        //$scope = 'blog:' . $app->blog->id;
        $ex_suffix = $plugin->get_config_value('suffix', system);
        if ( $ex_suffix != '' ) {
            $suffixes = explode(',', $ex_suffix);
            foreach($suffixes as $suffix){
                if ($suffix === $app->stash(extension)) {
                    return $content;
                }
            }
        }
        $excludedir = $plugin->get_config_value('exclude', system);
        if ( $excludedir != '' ) {
            $excludees = explode(',', $excludedir);
            foreach($excludees as $exclude){
                $exclude_pattern = '/^' . str_replace('/', '\/', preg_quote($exclude)) . '/';
                if (preg_match($exclude_pattern, $app->stash(path))) {
                    return $content;
                }
            }
        }
        if ( $ctx->mt->config('DynamicAbs2Rel') ) {
            $url_pattern = '/((<[^>]+\s(?:src|href|action)\=[\"\'])(https?:\/\/[^\/]+)\/([^\"\']+)([\"\']))/';
            preg_match_all($url_pattern, $content, $matches, PREG_SET_ORDER);
            foreach( $matches as $value ){
                if ( preg_replace('/\/$/', '', $app->base) === $value[3]) {
                    $target = $value[4];
                    if (preg_match('/\/$/', $target)) {
                        $target = preg_replace('/\/$/', '', $target);
                        if (preg_match('/^\//', $plugin->get_config_value('index', system))) {
                            $target .= $plugin->get_config_value('index', system);
                        } else {
                            $target .= '/' . $plugin->get_config_value('index', system);
                        }
                    } else {
                        if (preg_match('/\/[^\.]+$/', $target)) {
                            $lastpath = preg_replace('/^.*\/([^\.]+)$/', '$1', $target);
                            if (preg_match('/[^\.]/', $lastpath)) {
                                if (preg_match('/^\//', $plugin->get_config_value('index', system))) {
                                    $target .= $plugin->get_config_value('index', system);
                                } else {
                                    $target .= '/' . $plugin->get_config_value('index', system);
                                }
                            }
                        }
                    }
                    //
                    // http://www.sound-uz.jp/php/note/relativePath
                    //
                    $relative = '';
                    $basepath   = explode('/', preg_replace('/^\//', '', $app->path()));
                    $targetpath = explode('/', $target);
                    do {
                        $f = array_shift($basepath);
                        $t = array_shift($targetpath);
                    } while ($f === $t);
                    array_unshift($basepath, $f);
                    array_unshift($targetpath, $t);
                    $bcount = count($basepath);
                    $tcount = count($targetpath);
                    if ($bcount == 1 && $tcount == 1) {
                        $relative .= array_pop($targetpath);
                    } else {
                        if($bcount > 1) {
                            $relative = str_repeat('../', $bcount - 1);
                        }
                        $relative .= implode('/', $targetpath);
                    }
                    $pattern = '/' . str_replace('/', '\/', preg_quote($value[1])) . '/';
                    $replace = $value[2] . $relative . $value[5];
                    $content = preg_replace($pattern, $replace, $content);
                }
            }
        }
        return $content;
    }
}

?>