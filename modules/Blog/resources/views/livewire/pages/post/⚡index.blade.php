<?php

use Livewire\Component;
use Livewire\Attributes\Layout;

new #[Layout('blog::layouts.app')] class extends Component
{
    public function with(): array
    {
        return [
            'tenant' => tenant()?->getTenantKey(),
            'theme' => config('themer.active'),
            'module' => 'Blog',
        ];
    }
};
?>

<div>
    <h1>Blog Module</h1>
    <p>Tenant: {{ $tenant }}</p>
    <p>Active theme: {{ $theme }}</p>
    <p>Rendered from: <code>Modules/Blog/resources/views/livewire/pages/⚡post/index.blade.php</code></p>
</div>