<?php

declare(strict_types=1);

namespace Flagbit\Bundle\CategoryBundle\EventListener;

use Akeneo\Platform\Bundle\UIBundle\EventListener\ScriptNonceGenerator;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class InjectCategoryEditLoaderListener
{
    private ScriptNonceGenerator $nonceGenerator;

    public function __construct(ScriptNonceGenerator $nonceGenerator)
    {
        $this->nonceGenerator = $nonceGenerator;
    }

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

        $nonce = $this->nonceGenerator->getGeneratedNonce();

        $script = sprintf(
            '<script type="text/javascript" nonce="%s">
    console.log("[Flagbit] Inline script executing, require:", typeof require);
    if (typeof require !== "undefined") {
        try {
            var result = require("flagbit-category/property/category-edit-loader");
            console.log("[Flagbit] Module loaded:", result);
        } catch(e) {
            console.error("[Flagbit] Failed to load category-edit-loader:", e);
        }
    } else {
        console.warn("[Flagbit] require is not defined");
    }
</script>',
            htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8')
        );

        $content = str_replace('</body>', $script . '</body>', $content);
        $response->setContent($content);
    }
}
