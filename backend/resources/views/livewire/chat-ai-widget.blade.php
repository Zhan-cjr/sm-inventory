<div>
    <style>
        .ai-chat-btn {
            position: fixed; bottom: 24px; right: 24px; width: 56px; height: 56px; 
            background-color: var(--primary-600, #4f46e5); color: white; 
            border-radius: 9999px; display: flex; align-items: center; justify-content: center; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            cursor: pointer; z-index: 9999; transition: transform 0.2s; border: none;
        }
        .ai-chat-btn:hover { transform: scale(1.05); }
        .ai-chat-window {
            position: fixed; bottom: 90px; right: 24px; width: 380px; height: 500px; 
            background-color: white; border-radius: 16px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e5e7eb; 
            display: flex; flex-direction: column; overflow: hidden; z-index: 9999;
        }
        .dark .ai-chat-window { background-color: #111827; border-color: #374151; }
        .ai-chat-header {
            background-color: var(--primary-600, #4f46e5); padding: 12px 16px; 
            display: flex; align-items: center; justify-content: space-between; color: white;
        }
        .ai-chat-body {
            flex: 1; padding: 16px; overflow-y: auto; background-color: #f9fafb; 
            display: flex; flex-direction: column; gap: 12px;
        }
        .dark .ai-chat-body { background-color: #1f2937; }
        .ai-chat-bubble-user {
            background-color: var(--primary-600, #4f46e5); color: white; 
            padding: 10px 14px; border-radius: 16px; border-top-right-radius: 4px; 
            align-self: flex-end; max-width: 85%; font-size: 14px; line-height: 1.4;
        }
        .ai-chat-bubble-ai {
            background-color: white; color: #1f2937; 
            padding: 10px 14px; border-radius: 16px; border-top-left-radius: 4px; 
            align-self: flex-start; max-width: 90%; font-size: 14px; line-height: 1.4;
            border: 1px solid #e5e7eb; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        .dark .ai-chat-bubble-ai { background-color: #374151; color: #f3f4f6; border-color: #4b5563; }
        .ai-chat-input-area {
            padding: 12px; background-color: white; border-top: 1px solid #e5e7eb; 
            display: flex; gap: 8px;
        }
        .dark .ai-chat-input-area { background-color: #111827; border-color: #374151; }
        .ai-chat-input {
            flex: 1; border: 1px solid #d1d5db; border-radius: 9999px; padding: 8px 16px; 
            font-size: 14px; outline: none; background: #f9fafb; color: #111827;
        }
        .dark .ai-chat-input { background: #374151; border-color: #4b5563; color: white; }
        .ai-chat-input:focus { border-color: var(--primary-600, #4f46e5); }
        .ai-chat-submit {
            background-color: var(--primary-600, #4f46e5); color: white; 
            border: none; border-radius: 9999px; width: 38px; height: 38px; 
            display: flex; align-items: center; justify-content: center; cursor: pointer;
        }
        .ai-chat-submit:disabled { opacity: 0.5; cursor: not-allowed; }
        .ai-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 12px; }
        .ai-table th, .ai-table td { border: 1px solid #e5e7eb; padding: 6px; text-align: left; }
        .dark .ai-table th, .dark .ai-table td { border-color: #4b5563; }
    </style>

    <!-- Floating Button -->
    <button wire:click="toggleChat" class="ai-chat-btn">
        @if($isOpen)
            <x-heroicon-o-x-mark style="width: 28px; height: 28px;" />
        @else
            <x-heroicon-o-chat-bubble-left-ellipsis style="width: 28px; height: 28px;" />
            <span style="position: absolute; top: 0; right: 0; width: 12px; height: 12px; background-color: #ef4444; border: 2px solid white; border-radius: 9999px;"></span>
        @endif
    </button>

    <!-- Chat Window -->
    @if($isOpen)
    <div class="ai-chat-window">
        
        <!-- Header -->
        <div class="ai-chat-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <x-heroicon-o-sparkles style="width: 20px; height: 20px; color: white;" />
                <div>
                    <h3 style="margin: 0; font-size: 14px; font-weight: bold;">Asisten AI Toko</h3>
                    <p style="margin: 0; font-size: 10px; opacity: 0.8;">Tanya data penjualan, stok, dll</p>
                </div>
            </div>
            <button wire:click="toggleChat" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;">
                <x-heroicon-m-minus style="width: 20px; height: 20px;" />
            </button>
        </div>

        <!-- Chat Area -->
        <div id="chatAiScrollArea" class="ai-chat-body">
            @foreach($chatHistory as $chat)
                @if($chat['role'] === 'user')
                    <div class="ai-chat-bubble-user">
                        {{ $chat['content'] }}
                    </div>
                @else
                    <div class="ai-chat-bubble-ai">
                        <div style="margin-bottom: 4px;">
                            {!! Str::markdown($chat['content']) !!}
                        </div>
                        @if(!empty($chat['data']))
                            <div style="overflow-x: auto;">
                                <table class="ai-table">
                                    <thead>
                                        <tr>
                                            @foreach(array_keys($chat['data'][0]) as $key)
                                                <th>{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($chat['data'] as $row)
                                            <tr>
                                                @foreach($row as $val)
                                                    <td>{{ is_numeric($val) && strpos((string)$val, '.') === false ? number_format($val, 0, ',', '.') : $val }}</td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                @endif
            @endforeach

            @if($isLoading)
                <div class="ai-chat-bubble-ai" style="display: flex; gap: 4px; padding: 12px;">
                    Menganalisa data...
                </div>
            @endif
        </div>

        <!-- Input Area -->
        <form wire:submit.prevent="ask" class="ai-chat-input-area">
            <input 
                type="text" 
                wire:model="question" 
                placeholder="Ketik pertanyaan Anda..." 
                class="ai-chat-input"
                autocomplete="off"
                @if($isLoading) disabled @endif
            >
            <button type="submit" class="ai-chat-submit" @if($isLoading) disabled @endif>
                <x-heroicon-m-paper-airplane style="width: 18px; height: 18px; margin-left: 2px;" />
            </button>
        </form>

        <!-- Footer -->
        <div style="text-align: center; font-size: 10px; color: #9ca3af; background-color: white; padding-bottom: 8px;" class="dark:bg-gray-900">
            Dikembangkan oleh <strong>Amnal</strong>
        </div>
    </div>
    @endif

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('chat-updated', () => {
                setTimeout(() => {
                    const scrollArea = document.getElementById('chatAiScrollArea');
                    if(scrollArea) {
                        scrollArea.scrollTop = scrollArea.scrollHeight;
                    }
                }, 100);
            });
        });
    </script>
</div>
