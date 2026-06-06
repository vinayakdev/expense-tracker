<div class="min-h-screen flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

        <div class="text-center mb-8">
            <flux:heading size="xl">Set up your account</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                Create your first account to start tracking expenses
            </flux:text>
        </div>

        <div class="bg-white dark:bg-zinc-900 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-800 p-8">
            <form wire:submit="create" class="space-y-5">
                <flux:input
                    wire:model="name"
                    label="Account name"
                    placeholder="e.g. Personal, Business"
                    autocomplete="off"
                />

                <flux:select wire:model="currency" label="Currency">
                    <flux:select.option value="USD">USD — US Dollar</flux:select.option>
                    <flux:select.option value="EUR">EUR — Euro</flux:select.option>
                    <flux:select.option value="GBP">GBP — British Pound</flux:select.option>
                    <flux:select.option value="INR">INR — Indian Rupee</flux:select.option>
                    <flux:select.option value="AUD">AUD — Australian Dollar</flux:select.option>
                    <flux:select.option value="CAD">CAD — Canadian Dollar</flux:select.option>
                </flux:select>

                <flux:button type="submit" variant="primary" class="w-full">
                    Create account
                </flux:button>
            </form>
        </div>

    </div>
</div>
