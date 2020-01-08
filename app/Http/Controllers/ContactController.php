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

namespace App\Http\Controllers;

use App\Mail\Contact;
use App\Models\User;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

final class ContactController extends Controller
{
    /**
     * Contact Form.
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('contact.index');
    }

    /**
     * Send A Contact Email To Owner/First User.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        // Fetch owner account
        $user = User::where('id', '=', 3)->first();

        $input = $request->all();
        Mail::to($user->email, $user->username)->send(new Contact($input));

        return redirect()->route('home.index')
            ->withSuccess('Your Message Was Successfully Sent');
    }
}
