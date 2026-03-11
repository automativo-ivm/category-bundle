<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class InjectCategoryEditLoaderListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('content-type', '');

        if (stripos($contentType, 'text/html') === false && $contentType !== '') {
            return;
        }

        $content = $response->getContent();
        if ($content === false) {
            return;
        }

        if (strpos($content, '</body>') === false) {
            return;
        }

        $script = <<<'JS'
<script type="text/javascript">
    if (typeof require !== 'undefined') {
        try {
            require('flagbit-category/property/category-edit-loader');
        } catch(e) {
            console.error('[Flagbit] Failed to load category-edit-loader:', e);
        }
    }
</script>
JS;

        $content = str_replace('</body>', $script . '</body>', $content);
        $response->setContent($content);
    }
}
