<?php

/*
 * This file is part of StyleCI.
 *
 * (c) Cachet HQ <support@cachethq.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace StyleCI\StyleCI\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory as View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * This is the check for maintenance mode middleware class.
 *
 * @author Graham Campbell <graham@cachethq.io>
 */
class CheckForMaintenanceMode
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Contracts\Foundation\Application
     */
    protected $app;

    /**
     * The view factory instance.
     *
     * @var \Illuminate\Contracts\View\Factory
     */
    protected $view;

    /**
     * Create a new check for maintenance mode instance.
     *
     * @param \Illuminate\Contracts\Foundation\Application $app
     * @param \Illuminate\Contracts\View\Factory           $view
     *
     * @return void
     */
    public function __construct(Application $app, View $view)
    {
        $this->app = $app;
        $this->view = $view;
    }

    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->app->isDownForMaintenance()) {
            return new Response($this->view->make('maintenance')->render(), 503);
        }

        return $next($request);
    }
}
