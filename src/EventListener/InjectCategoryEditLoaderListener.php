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
    (function flagbitLoader() {
        if (typeof require === "function") {
            try {
                var fr = require("pim/fetcher-registry");
                fr.getFetcher("locale");
                require("flagbit-category/property/category-edit-loader");
                return;
            } catch(e) {}
        }
        setTimeout(flagbitLoader, 500);
    })();
</script>',
            htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8')
        );

        $content = str_replace('</body>', $script . '</body>', $content);
        $response->setContent($content);
    }
}
