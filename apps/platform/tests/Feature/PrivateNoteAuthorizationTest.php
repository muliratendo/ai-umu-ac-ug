<?php

namespace Tests\Feature;

use App\Authorization\ManagePrivateNote;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class PrivateNoteAuthorizationTest extends TestCase
{
    private const URI = '/_gate3/private-note';

    /** @var array<int|string, true> */
    private array $grants = [];

    private string $note = 'Private fixture text';

    private int $reads = 0;

    private int $writes = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $ability = new ManagePrivateNote(
            fn (int|string $id): bool => isset($this->grants[$id]),
        );
        Gate::define(ManagePrivateNote::ABILITY, $ability->allows(...));

        Route::middleware(['web', 'can:'.ManagePrivateNote::ABILITY])
            ->group(function (): void {
                Route::get(self::URI, function () {
                    $this->reads++;

                    return response()->json(['note' => $this->note]);
                });

                Route::patch(self::URI, function (Request $request) {
                    $data = $request->validate([
                        'note' => ['required', 'string', 'max:200'],
                    ]);

                    $this->writes++;
                    $this->note = $data['note'];

                    return response()->noContent();
                });
            });
    }

    public function test_granted_account_can_read_and_update_the_private_note(): void
    {
        $this->grants[101] = true;
        $this->actingAs($this->account(101), 'web');

        $this->getJson(self::URI)
            ->assertOk()
            ->assertExactJson(['note' => 'Private fixture text']);

        $this->patchJson(self::URI, ['note' => 'Updated fixture'])->assertNoContent();

        $this->getJson(self::URI)
            ->assertOk()
            ->assertExactJson(['note' => 'Updated fixture']);

        $this->assertSame(2, $this->reads);
        $this->assertSame(1, $this->writes);
    }

    public function test_basic_signed_in_account_cannot_read_or_update_the_note(): void
    {
        // A grant for another account must not authorize this account.
        $this->grants[101] = true;
        $this->actingAs($this->account(202), 'web');

        $this->getJson(self::URI)->assertForbidden();
        $this->patchJson(self::URI, ['note' => 'Denied update'])->assertForbidden();

        $this->assertSame('Private fixture text', $this->note);
        $this->assertSame(0, $this->reads);
        $this->assertSame(0, $this->writes);
    }

    public function test_revoking_grant_denies_the_next_requests_without_reauthentication(): void
    {
        $this->grants[101] = true;
        $this->actingAs($this->account(101), 'web');

        $this->getJson(self::URI)->assertOk();
        $this->patchJson(self::URI, ['note' => 'Before revocation'])->assertNoContent();

        unset($this->grants[101]);

        $this->getJson(self::URI)->assertForbidden();
        $this->patchJson(self::URI, ['note' => 'After revocation'])->assertForbidden();

        $this->assertAuthenticated('web');
        $this->assertSame('Before revocation', $this->note);
        $this->assertSame(1, $this->reads);
        $this->assertSame(1, $this->writes);
    }

    private function account(int $id): User
    {
        $user = new User;
        $user->setAttribute($user->getAuthIdentifierName(), $id);

        return $user;
    }
}
