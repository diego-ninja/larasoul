<?php

namespace Ninja\Larasoul\View\Composers;

use Illuminate\View\View;
use Ninja\Larasoul\Services\VerisoulScriptGenerator;

class VerisoulScriptComposer
{
    public function __construct(
        private VerisoulScriptGenerator $scriptGenerator
    ) {}

    /**
     * Bind Verisoul script data to the view.
     */
    public function compose(View $view): void
    {
        if (!$this->shouldInject()) {
            $view->with('verisoulScript', '');
            return;
        }

        $script = $this->scriptGenerator->generateAutoInjectScript();

        $view->with('verisoulScript', $script);
    }

    /**
     * Check if should inject Verisoul script.
     */
    private function shouldInject(): bool
    {
        if (!config('larasoul.verisoul.frontend.enabled', false)) {
            return false;
        }

        if (!config('larasoul.verisoul.frontend.auto_inject', false)) {
            return false;
        }

        // Don't inject for AJAX or API requests
        if (request()->ajax() || request()->wantsJson()) {
            return false;
        }

        // Check if route is excluded
        $excludedRoutes = config('larasoul.verisoul.frontend.excluded_routes', []);
        $currentPath = request()->path();
        
        foreach ($excludedRoutes as $pattern) {
            if (fnmatch($pattern, $currentPath)) {
                return false;
            }
        }

        return true;
    }
}