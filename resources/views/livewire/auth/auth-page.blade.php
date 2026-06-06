<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-4xl">

        {{-- Header --}}
        <div class="text-center mb-10">
            <flux:heading size="xl" class="text-zinc-900 dark:text-white">
                {{ config('app.name') }}
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Track your expenses effortlessly
            </flux:text>
        </div>

        {{-- Two-panel card --}}
        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 overflow-hidden">
            <div class="grid grid-cols-1 md:grid-cols-2">

                {{-- Login panel --}}
                <div class="p-8 md:p-10">
                    <div class="mb-6">
                        <flux:heading size="lg">Sign in</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                            Welcome back — sign in to your account
                        </flux:text>
                    </div>

                    <form wire:submit="login" class="space-y-5">
                        <flux:input
                            wire:model="loginEmail"
                            type="email"
                            label="Email address"
                            placeholder="you@example.com"
                            autocomplete="email"
                        />

                        <flux:input
                            wire:model="loginPassword"
                            type="password"
                            label="Password"
                            placeholder="••••••••"
                            autocomplete="current-password"
                        />

                        <div class="flex items-center justify-between">
                            <flux:checkbox wire:model="rememberMe" label="Remember me" />
                            <flux:link href="#" class="text-sm">Forgot password?</flux:link>
                        </div>

                        <flux:button type="submit" variant="primary" class="w-full">
                            Sign in
                        </flux:button>
                    </form>

                    @if ($quickLoginUsers->isNotEmpty())
                        <div class="mt-6">
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center">
                                    <div class="w-full border-t border-zinc-200 dark:border-zinc-700"></div>
                                </div>
                                <div class="relative flex justify-center text-xs">
                                    <span class="bg-white dark:bg-zinc-900 px-2 text-zinc-400">Quick login</span>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-col gap-2">
                                @foreach ($quickLoginUsers as $user)
                                    <flux:button
                                        wire:click="quickLogin({{ $user->id }})"
                                        variant="primary"
                                        class="w-full"
                                    >
                                        {{ $user->name }}
                                    </flux:button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Register panel --}}
                <div class="p-8 md:p-10 bg-zinc-50 dark:bg-zinc-900/50">
                    <div class="mb-6">
                        <flux:heading size="lg">Create account</flux:heading>
                        <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                            New here? Get started in seconds
                        </flux:text>
                    </div>

                    <form wire:submit="register" class="space-y-5">
                        <flux:input
                            wire:model="registerName"
                            type="text"
                            label="Full name"
                            placeholder="Jane Doe"
                            autocomplete="name"
                        />

                        <flux:input
                            wire:model="registerEmail"
                            type="email"
                            label="Email address"
                            placeholder="you@example.com"
                            autocomplete="email"
                        />

                        <flux:input
                            wire:model="registerPassword"
                            type="password"
                            label="Password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        />

                        <flux:input
                            wire:model="registerPasswordConfirmation"
                            type="password"
                            label="Confirm password"
                            placeholder="••••••••"
                            autocomplete="new-password"
                        />

                        <flux:button type="submit" variant="primary" class="w-full">
                            Create account
                        </flux:button>
                    </form>
                </div>

            </div>
        </div>

    </div>
</div>
