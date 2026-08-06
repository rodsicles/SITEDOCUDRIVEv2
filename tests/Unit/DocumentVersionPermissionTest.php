<?php

namespace Tests\Unit;

use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class DocumentVersionPermissionTest extends TestCase
{
    private function makeUser(int $id, string $roleName): User
    {
        $user = new User();
        $user->id = $id;
        $user->setRelation('role', new Role(['role_name' => $roleName]));

        return $user;
    }

    private function makeDocument(int $uploadedBy): Document
    {
        $document = new Document();
        $document->uploaded_by = $uploadedBy;

        return $document;
    }

    public function test_uploader_can_manage_versions(): void
    {
        $owner = $this->makeUser(7, 'Faculty Employee');

        $this->assertTrue($this->makeDocument(7)->canManageVersions($owner));
    }

    public function test_dean_can_manage_versions_of_other_uploads(): void
    {
        $dean = $this->makeUser(1, 'Dean');

        $this->assertTrue($this->makeDocument(7)->canManageVersions($dean));
    }

    public function test_secretary_can_manage_versions_of_other_uploads(): void
    {
        $secretary = $this->makeUser(2, 'Secretary');

        $this->assertTrue($this->makeDocument(7)->canManageVersions($secretary));
    }

    public function test_other_faculty_cannot_manage_versions(): void
    {
        $stranger = $this->makeUser(9, 'Faculty Employee');

        $this->assertFalse($this->makeDocument(7)->canManageVersions($stranger));
    }

    public function test_coordinator_cannot_replace_another_users_file(): void
    {
        $coordinator = $this->makeUser(5, 'Program Coordinator');

        $this->assertFalse($this->makeDocument(7)->canManageVersions($coordinator));
    }
}
