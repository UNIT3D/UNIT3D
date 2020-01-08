<?php

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D
 *
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 * @author     HDVinnie
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param Request     $request
     * @param Closure     $next
     * @param string|null $guard
     *
     * @return \Illuminate\Http\RedirectResponse|mixed
     */
    public function handle(\Illuminate\Http\Request $request, Closure $next, ?string $guard = null)
    {
        if (auth()->guard($guard)->check()) {
            return redirect()->route('home.index');
        }

        return $next($request);
    }
}
