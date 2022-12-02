<?php

namespace x\comment__guard__link {
    function comment($y) {
        // Convert `<a>` to `<span>`
        if (isset($y[1]['header'][1]['author'][1]['link'])) {
            $y[1]['header'][1]['author'][1]['link'][0] = 'span';
            $y[1]['header'][1]['author'][1]['link'][2] = [];
        }
        if (isset($y[1]['body'][1]['content'][1])) {
            $content = (string) $y[1]['body'][1]['content'][1];
            // Strip anchor tag(s) from comment content
            $content = \preg_replace('/<a(?:\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*)?>|<\/a>/i', "", $content);
            $y[1]['body'][1]['content'][1] = $content;
        }
        return $y;
    }
    function form($y) {
        // Remove `link` field in the default comment form
        unset($y[1]['link']);
        return $y;
    }
    function route($content, $path, $query) {
        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            \extract($GLOBALS, \EXTR_SKIP);
            // Remove the `link` data before it gets to the comment saving process route
            unset($_POST['comment']['link']);
            // Limit number of link(s) in comment content
            $max = (int) ($state->x->{'comment.guard.link'}->content ?? 5);
            if ($max >= 0) {
                $test = $_POST['comment']['content'] ?? "";
                if (\substr_count(\strtolower($test), '</a>') > $max) {
                    \class_exists("\\Alert") && \Alert::error('Too many links in the comment.');
                    foreach (['author', 'content', 'email'] as $v) {
                        $_SESSION['form']['comment'][$v] = $_POST['comment'][$v] ?? null;
                    }
                    \kick($path . $query . '#comment');
                }
                if (false !== \strpos($test, '://') && \preg_match_all('/\bhttps?:\/\/\S+/', \strip_tags($test), $m)) {
                    if (\count($m[0]) > $max) {
                        \class_exists("\\Alert") && \Alert::error('Too many links in the comment.');
                        foreach (['author', 'content', 'email'] as $v) {
                            $_SESSION['form']['comment'][$v] = $_POST['comment'][$v] ?? null;
                        }
                        \kick($path . $query . '#comment');
                    }
                }
            }
        }
        return $content;
    }
    $link = $state->x->{'comment.guard.link'}->link ?? 0;
    if (false === $link || $link < 1) {
        \Hook::set('route.comment', __NAMESPACE__ . "\\route", 0);
        \Hook::set('y.form.comment', __NAMESPACE__ . "\\form", 100);
    }
    if (false !== $link && $link < 0) {
        \Hook::set('y.comment', __NAMESPACE__ . "\\comment", 100);
    }
}