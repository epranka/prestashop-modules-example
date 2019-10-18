<?php

require_once __DIR__ . '/../../tools/js_minify/jsmin.php';
require_once __DIR__ . '/../../tools/JShrink/Minifier.php';
require_once __DIR__ . '/../../tools/minify_html/minify_html.class.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class KodminaOptimization extends Module
{
    public function __construct()
    {
        $this->name = 'kodminaoptimization';
        $this->tab = 'front_office_features';
        $this->version = '0.9.0';
        $this->author = 'Kodmina. Edvinas Pranka';
        $this->need_instance = 0;
        $this->ps_version_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        $this->css_chunk_size = 150 * 1000;
        $this->subpath_length = 8;
        $this->exclude_css = array();
        $this->exclude_js = array();

        parent::__construct();

        $this->displayName = $this->l('Kodmina Optimization');
        $this->description = $this->l('Optimize front end');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    private function compressCss($buffer)
    {
        // Remove comments
        $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
        // Remove space after colons
        $buffer = str_replace(': ', ':', $buffer);
        // Remove whitespace
        $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
        return $buffer;
    }

    private function compressJs($javascript)
    {
        $blocks = array('for', 'while', 'if', 'else');
        $javascript = preg_replace('/([-\+])\s+\+([^\s;]*)/', '$1 (+$2)', $javascript);
        // remove new line in statements
        $javascript = preg_replace('/\s+\|\|\s+/', ' || ', $javascript);
        $javascript = preg_replace('/\s+\&\&\s+/', ' && ', $javascript);
        $javascript = preg_replace('/\s*([=+-\/\*:?])\s*/', '$1 ', $javascript);
        // handle missing brackets {}
        foreach ($blocks as $block) {
            $javascript = preg_replace('/(\s*\b' . $block . '\b[^{\n]*)\n([^{\n]+)\n/i', '$1{$2}', $javascript);
        }
        // handle spaces
        $javascript = preg_replace(array("/\s*\n\s*/", "/\h+/"), array("\n", " "), $javascript); // \h+ horizontal white space
        $javascript = preg_replace(array('/([^a-z0-9\_])\h+/i', '/\h+([^a-z0-9\$\_])/i'), '$1', $javascript);
        $javascript = preg_replace('/\n?([[;{(\.+-\/\*:?&|])\n?/', '$1', $javascript);
        $javascript = preg_replace('/\n?([})\]])/', '$1', $javascript);
        $javascript = str_replace("\nelse", "else", $javascript);
        $javascript = preg_replace("/([^}])\n/", "$1;", $javascript);
        $javascript = preg_replace("/;?\n/", ";", $javascript);
        return $javascript;
    }

    private function normalizePath($path)
    {
        return array_reduce(explode('/', $path), create_function('$a, $b', '
                if($a === 0)
                    $a = "/";

                if($b === "" || $b === ".")
                    return $a;

                if($b === "..")
                    return dirname($a);

                return preg_replace("/\/+/", "/", "$a/$b");
            '), 0);
    }

    private function isAbsolutePath($path)
    {
        if (!is_string($path)) {
            $mess = sprintf('String expected but was given %s', gettype($path));
            throw new \InvalidArgumentException($mess);
        }
        if (!ctype_print($path)) {
            $mess = 'Path can NOT have non-printable characters or be empty';
            throw new \DomainException($mess);
        }
        // Optional wrapper(s).
        $regExp = '%^(?<wrappers>(?:[[:print:]]{2,}://)*)';
        // Optional root prefix.
        $regExp .= '(?<root>(?:[[:alpha:]]:/|/)?)';
        // Actual path.
        $regExp .= '(?<path>(?:[[:print:]]*))$%';
        $parts = [];
        if (!preg_match($regExp, $path, $parts)) {
            $mess = sprintf('Path is NOT valid, was given %s', $path);
            throw new \DomainException($mess);
        }
        if ('' !== $parts['root']) {
            return true;
        }
        return false;
    }

    private function rmdir_recursive($dir)
    {
        if (!file_exists($dir)) {
            return;
        }

        foreach (scandir($dir) as $file) {
            if ('.' === $file || '..' === $file) {
                continue;
            }

            if (is_dir("$dir/$file")) {
                rmdir_recursive("$dir/$file");
            } else {
                unlink("$dir/$file");
            }

        }
        rmdir($dir);
    }

    private function mkdir($dir)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }
    }

    private function isBase64($s) {
       return preg_match('/^data\:[a-z0-9\+]+\/[a-z0-9\+]+\;base64,/mi', $s);
    }

    public function optimizeHtmlOutput($html)
    {
        if(defined('_PS_KODMINA_OPTIMIZATION_') && _PS_KODMINA_OPTIMIZATION_ === false) {
            return $html;
        }
        $that = $this;
        $minified = $html;

        $minified = Minify_HTML::minify($minified, array('jsCleanComments' => true, 'jsMinifier' => function ($js_content) {
            return JShrinkMinifier::minify($js_content);
        }, 'cssMinifier' => function ($css_content) use ($that) {
            return $that->compressCss($css_content);
        }));


        // combine inline js, and move all scripts to end
        preg_match_all('/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i', $minified, $js_matches);
        $combined_inline_js = "";
        $other_js = "";
        if ($js_matches) {
            
            /** TRUNCATED */

            // $combined_inline_js = escapeJavaScriptText(json_encode($combined_inline_js));
            // $combined_inline_js = str_replace('\\\"', '"', $combined_inline_js);
            $minified = preg_replace('/(\\s*)<script(\\b[^>]*?>)([\\s\\S]*?)<\\/script>(\\s*)/i', '', $minified);
            $minified = preg_replace('/<\/body>\s*<\/html>/i', '_KODMINA_MINIFIED_JS_6caa522ebb3969167cff47549a69a5b5_', $minified);
            $minified = str_replace('_KODMINA_MINIFIED_JS_6caa522ebb3969167cff47549a69a5b5_', $other_js . '<script>'.$combined_inline_js.'</script></body></html>', $minified);
            // $minified = preg_replace('/<\/body>\s*<\/html>/i', $other_js . '<script>'.$combined_inline_js.'</script></body></html>', $minified);
        }
        return $minified;
    }

    public function optimizeCssResources($css_files)
    {
        if(defined('_PS_KODMINA_OPTIMIZATION_') && _PS_KODMINA_OPTIMIZATION_ === false) {
            return array('content' => '', 'css_files' => $css_files);
        }
        $baseUri = $this->context->shop->getBaseURI();
        $baseUri = $baseUri == '/' ? '' : $baseUri;
        $cache_id = md5(json_encode($css_files));
        $subpath = substr($cache_id, 0, $this->subpath_length);
        // $cache = Cache::getInstance();
        // if ($cache->exists($cache_id)) {
        //     return $cache->get($cache_id);
        // }
        array_map('unlink', array_filter((array) glob(__DIR__ . '/compiled/' . $subpath . '/*.css')));
        $this->rmdir_recursive(__DIR__ . '/compiled/ ' . $subpath);
        $this->mkdir(__DIR__ . '/compiled/' . $subpath, 0775, true);
        $combined = "";
        $whole_content = "";
        $css_files_result = $css_files;
        // foreach all css files
        foreach ($css_files as $css_uri => $media) {
            if ($media == 'all') {
                // if css media is 'all'
                if (in_array($css_uri, $this->exclude_css)) {
                    // exclude css
                    unset($css_files_result[$css_uri]);
                    continue;
                } else if ($this->isAbsolutePath($css_uri)) {
                    // remove from css_files
                    unset($css_files_result[$css_uri]);
                    $css_uri = '/' . ltrim($css_uri, $baseUri);

                    // read css file
                    $css_content = file_get_contents(__DIR__ . '/../..' . $css_uri);
                    // matches all url definitions inside the css file as follows:
                    // url("*")  URL definitions with double quotes
                    // url('*')  URL definitions with single quotes
                    // url(*)    URL definitions without quotes
                    preg_match_all('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $css_content, $matches, PREG_PATTERN_ORDER);
                    if ($matches) {
                        foreach ($matches[3] as $match) {
                            $is_base64 = $this->isBase64($match);
                            if(!$is_base64) {
                                $new_url = $this->normalizePath(dirname($css_uri) . '/' . $match);
                                $css_content = str_replace($match, $new_url, $css_content);
                            }
                        }
                    }
                    
                    /** TRUNCATED */
                
                } else if (filter_var($css_uri, FILTER_VALIDATE_URL)) {
                    unset($css_files_result[$css_uri]);
                    $css_content = file_get_contents($css_uri);
                    $combined .= $css_content;
                    $whole_content .= $css_content;
                    $combined = $this->compressCss($combined);
                    
                    /** TRUNCATED */

                } else {
                    $css_files_result[$css_uri] = 'all';
                }
            }
        }

        $combined = $this->compressCss($combined);
        $hash = substr(md5(mt_rand()), 0, 14);
        file_put_contents(__DIR__ . '/compiled/' . $subpath . '/' . $hash . '.css', $combined);
        $css_files_result[$baseUri . '/modules/kodminaoptimization/compiled/' . $subpath . '/' . $hash . '.css'] = 'all';
        $css_files = $css_files_result;
        $result = array(
            'css_files' => $css_files,
            'content' => $this->compressCss($whole_content)
        );
        // $cache->set($cache_id, $result, 0);
        return $result;
    }

    private function removeJsComments($output) {
        $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\')\/\/.*))/';
        $output = preg_replace($pattern, '', $output);
        return $output;
    }

    public function optmizeJsResources($js_files)
    {
        if(defined('_PS_KODMINA_OPTIMIZATION_') && _PS_KODMINA_OPTIMIZATION_ === false) {
            return array('content' => '', 'js_files' => $js_files);
        }
        $baseUri = $this->context->shop->getBaseURI();
        $baseUri = $baseUri == '/' ? '' : $baseUri;        
        $cache_id = md5(json_encode($js_files));
        $subpath = substr($cache_id, 0, $this->subpath_length);
        $cache = Cache::getInstance();
        if ($cache->exists($cache_id)) {
            return $cache->get($cache_id);
        }
        array_map('unlink', array_filter((array) glob(__DIR__ . '/compiled/' . $subpath . '/*.js')));
        $this->rmdir_recursive(__DIR__ . '/compiled/ ' . $subpath);
        $this->mkdir(__DIR__ . '/compiled/' . $subpath, 0775, true);
        $combined = "";
        $whole_content = "";
        $js_files_result = $js_files;
        foreach ($js_files as $key => $js_uri) {
            if (in_array($js_uri, $this->exclude_js)) {
                // exclude js
                $js_files_result = array_diff($js_files_result, [$js_uri]);
                continue;
            } else if ($this->isAbsolutePath($js_uri)) {
            
                /** TRUNCATED */

            } else if (filter_var($js_uri, FILTER_VALIDATE_URL)) {
                $js_content = file_get_contents($js_uri);
                $js_files_result = array_diff($js_files_result, [$js_uri]);
                $combined .= $js_content;
                $whole_content .= $js_content;
            } else {
                $hash = substr(md5(mt_rand()), 0, 14);
                if (!$combined) {
                    continue;
                }

                $combined = JShrinkMinifier::minify($combined);
                file_put_contents(__DIR__ . '/compiled/' . $subpath . '/' . $hash . '.js', $combined);
                $js_files_result[] = $baseUri . '/modules/kodminaoptimization/compiled/' . $subpath . '/' . $hash . '.js';
                $combined = "";
                $js_files_result[] = $js_uri;
            }
        }
        // $combined = JSMin::minify($combined);
        $combined = JShrinkMinifier::minify($combined);
        $hash = substr(md5(mt_rand()), 0, 14);
        file_put_contents(__DIR__ . '/compiled/' . $subpath . '/' . $hash . '.js', $combined);
        $js_files_result[] = $baseUri . '/modules/kodminaoptimization/compiled/' . $subpath . '/' . $hash . '.js';
        $js_files = $js_files_result;
        $result = array(
            'js_files' => $js_files,
            'content' => JShrinkMinifier::minify($whole_content)
            // 'content' => JSMin::minify($whole_content)
        );
        $cache->set($cache_id, $result, 0);
        return $result;
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {
        if (!parent::uninstall()) {
            return false;
        }

        return true;
    }
}
