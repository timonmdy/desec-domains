<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\{identifier};

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\View\View;
use InvalidArgumentException;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\ActionRouter;
use Pterodactyl\BlueprintFramework\Extensions\desec\Admin\PageRegistry;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Http\Controllers\Controller;
use RuntimeException;

class {identifier}ExtensionController extends Controller
{
    public function __construct(
        private ViewFactory $view,
        private BlueprintExtensionLibrary $blueprint,
        private PageRegistry $pages,
        private ActionRouter $actions,
    ) {
    }

    public function index(Request $request): View
    {
        $this->guard($request);
        $page = $this->pages->resolve((string) $request->query('view', 'settings'));

        return $this->view->make('admin.extensions.{identifier}.index', [
            'root' => '/admin/extensions/{identifier}',
            'blueprint' => $this->blueprint,
            'page' => $page->name(),
            'data' => $page->data($request),
        ]);
    }

    public function post(Request $request): RedirectResponse
    {
        $this->guard($request);

        try {
            session()->flash('desec_success', $this->actions->run($request));
        } catch (InvalidArgumentException | RuntimeException $exception) {
            session()->flash('desec_error', $exception->getMessage());

            return redirect()->back()->withInput($request->except(['token', 'apiToken']));
        }

        return redirect()->back();
    }

    public function update(Request $request): RedirectResponse
    {
        return $this->post($request);
    }

    public function put(Request $request): RedirectResponse
    {
        return $this->post($request);
    }

    /** Defense in depth: DNS changes and API tokens are for root administrators only. */
    private function guard(Request $request): void
    {
        abort_unless((bool) $request->user()?->root_admin, 403);
    }
}
