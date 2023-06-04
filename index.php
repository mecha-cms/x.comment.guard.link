<?php namespace x\comment__guard__link;

// Disable this extension if `comment` extension is disabled or removed ;)
if (!isset($state->x->comment)) {
    return;
}

function y__comment($y) {
    \extract($GLOBALS, \EXTR_SKIP);
    $content = (int) ($state->x->{'comment.guard.link'}->content ?? 5);
    $link = (int) ($state->x->{'comment.guard.link'}->link ?? 0);
    // Strip anchor tag(s) in comment content
    if ($content < 0 && isset($y[1]['body'][1]['content'][1])) {
        $content = (string) $y[1]['body'][1]['content'][1];
        $content = \preg_replace('/<a(?:\s(?:"[^"]*"|\'[^\']*\'|[^\/>])*)?>|<\/a>/i', "", $content);
        $y[1]['body'][1]['content'][1] = $content;
    }
    // Convert `<a>` to `<span>`
    if ($link < 0 && isset($y[1]['header'][1]['author'][1]['link'])) {
        $y[1]['header'][1]['author'][1]['link'][0] = 'span';
        $y[1]['header'][1]['author'][1]['link'][2] = [];
    }
    return $y;
}

\Hook::set('y.comment', __NAMESPACE__ . "\\y__comment", 100);

$link = $state->x->{'comment.guard.link'}->link ?? 0;
if (false === $link || $link < 1) {
    function route__comment($content, $path, $query, $hash) {
        if ('POST' !== $_SERVER['REQUEST_METHOD']) {
            return $content;
        }
        \extract($GLOBALS, \EXTR_SKIP);
        if (isset($state->x->user) && \Is::user()) {
            return $content; // Disable the security if current user is logged-in
        }
        // Limit number of link(s) in the comment
        $max = $state->x->{'comment.guard.link'}->content ?? 5;
        $max = $max < 0 || false === $max ? 0 : $max;
        if (true !== $max) {
            $test = $_POST['comment']['content'] ?? "";
            if (\substr_count(\strtolower($test), '</a>') > $max) {
                foreach (['author', 'content', 'email'] as $v) {
                    $_SESSION['form']['comment'][$v] = $_POST['comment'][$v] ?? null;
                }
                \class_exists("\\Alert") && \Alert::error(0 === $max ? 'Links are not allowed in the comment.' : 'Too many links in the comment.');
                \kick($path . $query . ($hash ?? '#comment'));
            }
            if (false !== \strpos($test, '://') && \preg_match_all('/\bhttps?:\/\/\S+/', \strip_tags($test), $m)) {
                if (\count($m[0]) > $max) {
                    foreach (['author', 'content', 'email'] as $v) {
                        $_SESSION['form']['comment'][$v] = $_POST['comment'][$v] ?? null;
                    }
                    \class_exists("\\Alert") && \Alert::error(0 === $max ? 'Links are not allowed in the comment.' : 'Too many links in the comment.');
                    \kick($path . $query . ($hash ?? '#comment'));
                }
            }
        }
        // Remove the submitted `link` data before it gets to the comment saving process route
        unset($_POST['comment']['link']);
        return $content;
    }
    function y__form__comment($y) {
        \extract($GLOBALS, \EXTR_SKIP);
        if (isset($state->x->user) && \Is::user()) {
            return $y; // Disable the security if current user is logged-in
        }
        // Remove `link` field in the default comment form
        unset($y[1]['link']);
        return $y;
    }
    \Hook::set('route.comment', __NAMESPACE__ . "\\route__comment", 0);
    \Hook::set('y.form.comment', __NAMESPACE__ . "\\y__form__comment", 100);
}