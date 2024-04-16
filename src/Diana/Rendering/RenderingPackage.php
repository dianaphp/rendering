<?php

namespace Diana\Rendering;

use Diana\IO\Kernel;
use Diana\Rendering\Components\Component;
use Diana\Rendering\Components\DynamicComponent;
use Diana\Rendering\Contracts\Renderer;
use Diana\Rendering\Engines\CompilerEngine;
use Diana\Rendering\Engines\FileEngine;
use Diana\Rendering\Engines\PhpEngine;
use Diana\Runtime\Container;
use Diana\Runtime\Package;
use Diana\Support\Helpers\Filesystem;

class RenderingPackage extends Package
{
    function isDev(string $vite_host, string $entry): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }
        $handle = curl_init($vite_host . '/' . $entry);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_NOBODY, true);

        curl_exec($handle);
        $error = curl_errno($handle);
        curl_close($handle);

        return $exists = !$error;
    }

    public function getConfigDefault(): array
    {
        return [
            'renderCachePath' => './tmp/rendering/cached',
            'renderCompilationPath' => './tmp/rendering/compiled',

            'viteEnv' => 'prod',
            'viteHost' => 'http://localhost:3000'
        ];
    }

    public function getConfigFile(): string|null
    {
        return 'rendering';
    }

    public function getConfigCreate(): bool
    {
        return true;
    }

    public function getConfigAppend(): bool
    {
        return true;
    }

    public function __construct(Container $container, Kernel $kernel)
    {
        $this->loadConfig();

        Component::setCompilationPath(Filesystem::absPath($this->config['renderCompilationPath']));

        $compiler = new Compiler(Filesystem::absPath($this->config['renderCachePath']), false); // TODO: remove last argument to enable caching once everything works
        $compiler->component('dynamic-component', DynamicComponent::class);
        $compiler->directive("vite", function ($entry) {
            $entry = trim($entry, "\"'");

            if ($this->config['viteEnv'] == 'dev') {
                return
                    '<script type="module">
                        import RefreshRuntime from "' . $this->config['viteHost'] . '/@react-refresh"
                        RefreshRuntime.injectIntoGlobalHook(window)
                        window.$RefreshReg$ = () => {}
                        window.$RefreshSig$ = () => (type) => type
                        window.__vite_plugin_react_preamble_installed__ = true
                    </script>
                    <script type="module" src="' . $this->config['viteHost'] . '/@vite/client"></script>
                    <script type="module" src="' . $this->config['viteHost'] . '/' . $entry . '"></script>';
            } else {
                $content = file_get_contents(Filesystem::absPath('./dist/.vite/manifest.json'));
                $manifest = json_decode($content, true);

                $script = isset ($manifest[$entry]) ? "<script type=\"module\" src=\"" . $manifest[$entry]['file'] . "\"></script>" : "";

                foreach ($manifest[$entry]['imports'] ?? [] as $imports) $script .= "\n<link rel=\"modulepreload\" href=\"/" . $manifest[$imports]['file'] . "\">";
                foreach ($manifest[$entry]['css'] ?? [] as $file) $script .= "\n<link rel=\"stylesheet\" href=\"/$file\">";

                return $script;
            }
        });

        $bladeEngine = new CompilerEngine($compiler);

        $renderer = new Driver($compiler);

        $renderer->registerEngine('blade.php', $bladeEngine);
        $renderer->registerEngine('php', PhpEngine::class);
        $renderer->registerEngine(['html', 'css'], FileEngine::class);

        $kernel->terminating(static function () use ($bladeEngine) {
            Component::flushCache();
            $bladeEngine->forgetCompiledOrNotExpired();
        });

        $container->instance(Compiler::class, $compiler);
        $container->instance(Renderer::class, $renderer);
    }

    public function boot(): void
    {

    }
}