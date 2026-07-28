<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Regression coverage for the "419 Page Expired" bug that appeared when an
 * administrator restored a soft-deleted customer.
 */
class CustomerRestoreTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        Admin::create([
            'name'     => 'Admin Tester',
            'email'    => 'admin@example.test',
            'password' => Hash::make('secret123'),
            'role'     => 'Super Admin',
        ]);

        return ['admin_id' => 1, 'admin_email' => 'admin@example.test'];
    }

    private function trashedCustomer(): User
    {
        $user = User::create([
            'first_name' => 'Resto',
            'last_name'  => 'Tester',
            'email'      => 'resto@example.test',
            'phone'      => '09990001111',
            'address'    => 'Test Address',
            'password'   => Hash::make('secret123'),
        ]);

        $user->delete();

        return $user;
    }

    public function test_restore_redirects_to_customers_page_with_success_notification(): void
    {
        $user = $this->trashedCustomer();

        $response = $this->withSession($this->admin())
            ->post("/admin/customers/{$user->id}/restore");

        $response->assertRedirect(route('admin.customers.index'));
        $response->assertSessionHas('success');

        $this->assertFalse($user->fresh()->trashed());
        $this->assertNull($user->fresh()->deleted_by);
    }

    public function test_restore_via_ajax_returns_json_instead_of_419(): void
    {
        $user = $this->trashedCustomer();

        $response = $this->withSession($this->admin())
            ->postJson("/admin/customers/{$user->id}/restore");

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $this->assertFalse($user->fresh()->trashed());
    }

    /**
     * Laravel's VerifyCsrfToken middleware short-circuits while running unit
     * tests, so drive the exception handler directly to prove a token mismatch
     * renders a friendly redirect rather than the "419 Page Expired" screen.
     */
    public function test_stale_csrf_token_renders_redirect_instead_of_419_page(): void
    {
        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        $request = \Illuminate\Http\Request::create(
            route('admin.customers.restore', 1), 'POST'
        );
        $request->headers->set('referer', route('admin.customers.deleted'));
        $request->setLaravelSession($this->app['session.store']);

        // redirect()->back() resolves the "previous" URL from the container's
        // request instance, so bind the one we just built.
        $this->app->instance('request', $request);
        $this->app['url']->setRequest($request);

        $response = $handler->render(
            $request,
            new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.')
        );

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame(route('admin.customers.deleted'), $response->headers->get('Location'));
        $this->assertNotEmpty(session('error'));
    }

    public function test_stale_csrf_token_on_ajax_returns_json_error(): void
    {
        $handler = $this->app->make(\Illuminate\Contracts\Debug\ExceptionHandler::class);

        $request = \Illuminate\Http\Request::create(
            route('admin.customers.restore', 1), 'POST', [], [], [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest', 'HTTP_ACCEPT' => 'application/json']
        );
        $request->setLaravelSession($this->app['session.store']);

        $response = $handler->render(
            $request,
            new \Illuminate\Session\TokenMismatchException('CSRF token mismatch.')
        );

        $this->assertSame(419, $response->getStatusCode());
        $this->assertFalse(json_decode($response->getContent(), true)['success']);
    }
}
