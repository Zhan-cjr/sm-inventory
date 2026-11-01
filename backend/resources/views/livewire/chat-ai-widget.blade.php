<div x-data="aiDraggableWidget()"
     x-init="init()"
     @resize.window.debounce.150ms="onResize()"
     @pointermove.window="onDrag($event)"
     @pointerup.window="stopDrag($event)"
     @pointercancel.window="stopDrag($event)">

    <style>
        .ai-chat-btn {
            position: fixed; width: 56px; height: 56px; 
            bottom: 120px; right: 24px;
            background-color: var(--primary-600, #4f46e5); color: white; 
            border-radius: 9999px; display: flex; align-items: center; justify-content: center; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); 
            cursor: grab; z-index: 9999; border: none;
            touch-action: none; user-select: none; -webkit-user-select: none;
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }
        .ai-chat-btn:hover { transform: scale(1.05); }
        .ai-chat-btn:active, .ai-chat-btn.is-dragging {
            cursor: grabbing !important;
            transform: scale(1.08);
            box-shadow: 0 25px 35px -5px rgba(0, 0, 0, 0.25), 0 15px 15px -5px rgba(0, 0, 0, 0.15);
        }
        .ai-chat-window {
            position: fixed; width: 380px; height: 500px; 
            background-color: white; border-radius: 16px; 
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e5e7eb; 
            display: flex; flex-direction: column; overflow: hidden; z-index: 9999;
        }
        .dark .ai-chat-window { background-color: #111827; border-color: #374151; }
        .ai-chat-header {
            background-color: var(--primary-600, #4f46e5); padding: 12px 16px; 
            display: flex; align-items: center; justify-content: space-between; color: white;
            user-select: none;
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

    <!-- Floating Draggable Button -->
    <button 
        type="button"
        @pointerdown="startDrag($event)"
        @click="handleClick($event, $wire)"
        :style="buttonStyle"
        :class="{ 'is-dragging': isDragging }"
        class="ai-chat-btn"
        title="Klik untuk membuka chat, tahan & geser untuk memindahkan posisi"
    >
        @if($isOpen)
            <x-heroicon-o-x-mark style="width: 28px; height: 28px;" />
        @else
            <x-heroicon-o-chat-bubble-left-ellipsis style="width: 28px; height: 28px;" />
            <span style="position: absolute; top: 0; right: 0; width: 12px; height: 12px; background-color: #ef4444; border: 2px solid white; border-radius: 9999px;"></span>
        @endif
    </button>

    <!-- Chat Window Anchored to Button -->
    @if($isOpen)
    <div class="ai-chat-window" :style="windowStyle">
        
        <!-- Header -->
        <div class="ai-chat-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <x-heroicon-o-sparkles style="width: 20px; height: 20px; color: white;" />
                <div>
                    <h3 style="margin: 0; font-size: 14px; font-weight: bold;">Asisten AI Toko</h3>
                    <p style="margin: 0; font-size: 10px; opacity: 0.8;">Tanya data penjualan, stok, dll</p>
                </div>
            </div>
            <button wire:click="toggleChat" style="background: none; border: none; color: white; cursor: pointer; padding: 4px;" title="Tutup / Perkecil">
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
        window.aiDraggableWidget = window.aiDraggableWidget || function() {
            return {
                x: typeof window !== 'undefined' ? Math.max(16, window.innerWidth - 80) : 0,
                y: typeof window !== 'undefined' ? Math.max(16, window.innerHeight - 150) : 0,
                isDragging: false,
                hasMoved: false,
                dragStartX: 0,
                dragStartY: 0,
                initialX: 0,
                initialY: 0,

                init() {
                    const btnSize = 56;
                    const pad = 16;
                    let posX = Math.max(pad, window.innerWidth - btnSize - 24);
                    let posY = Math.max(pad, window.innerHeight - 150);

                    try {
                        const saved = localStorage.getItem('ai_chat_btn_pos');
                        if (saved) {
                            const parsed = JSON.parse(saved);
                            if (typeof parsed.x === 'number' && typeof parsed.y === 'number') {
                                posX = parsed.x;
                                posY = parsed.y;
                            }
                        }
                    } catch (e) {}

                    const maxX = Math.max(pad, window.innerWidth - btnSize - pad);
                    const maxY = Math.max(pad, window.innerHeight - btnSize - pad);
                    this.x = Math.min(Math.max(pad, posX), maxX);
                    this.y = Math.min(Math.max(pad, posY), maxY);
                },

                onResize() {
                    const btnSize = 56;
                    const pad = 16;
                    const maxX = Math.max(pad, window.innerWidth - btnSize - pad);
                    const maxY = Math.max(pad, window.innerHeight - btnSize - pad);
                    this.x = Math.min(Math.max(pad, this.x), maxX);
                    this.y = Math.min(Math.max(pad, this.y), maxY);
                },

                startDrag(e) {
                    if (e.pointerType === 'mouse' && e.button !== 0) return;
                    this.isDragging = true;
                    this.hasMoved = false;
                    this.dragStartX = e.clientX;
                    this.dragStartY = e.clientY;
                    this.initialX = this.x;
                    this.initialY = this.y;
                },

                onDrag(e) {
                    if (!this.isDragging) return;
                    const dx = e.clientX - this.dragStartX;
                    const dy = e.clientY - this.dragStartY;

                    if (!this.hasMoved && Math.hypot(dx, dy) > 5) {
                        this.hasMoved = true;
                    }

                    if (this.hasMoved) {
                        const btnSize = 56;
                        const pad = 12;
                        const maxX = Math.max(pad, window.innerWidth - btnSize - pad);
                        const maxY = Math.max(pad, window.innerHeight - btnSize - pad);
                        this.x = Math.min(Math.max(pad, this.initialX + dx), maxX);
                        this.y = Math.min(Math.max(pad, this.initialY + dy), maxY);
                    }
                },

                stopDrag(e) {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    if (this.hasMoved) {
                        try {
                            localStorage.setItem('ai_chat_btn_pos', JSON.stringify({ x: this.x, y: this.y }));
                        } catch (e) {}
                        setTimeout(() => {
                            this.hasMoved = false;
                        }, 120);
                    }
                },

                handleClick(e, wire) {
                    if (this.hasMoved) {
                        e.preventDefault();
                        e.stopPropagation();
                        return;
                    }
                    if (wire && typeof wire.toggleChat === 'function') {
                        wire.toggleChat();
                    } else if (this.$wire && typeof this.$wire.toggleChat === 'function') {
                        this.$wire.toggleChat();
                    }
                },

                get buttonStyle() {
                    return `position: fixed; left: ${this.x}px; top: ${this.y}px; bottom: auto; right: auto;`;
                },

                get windowStyle() {
                    const winWidth = Math.min(380, window.innerWidth - 24);
                    const winHeight = Math.min(500, window.innerHeight - 80);

                    let left;
                    if (this.x + 28 > window.innerWidth / 2) {
                        left = (this.x + 56) - winWidth;
                    } else {
                        left = this.x;
                    }
                    left = Math.max(12, Math.min(window.innerWidth - winWidth - 12, left));

                    let top;
                    if (this.y - winHeight - 12 >= 12) {
                        top = this.y - winHeight - 12;
                    } else if (this.y + 56 + 12 + winHeight <= window.innerHeight - 12) {
                        top = this.y + 56 + 12;
                    } else {
                        top = Math.max(12, Math.min(window.innerHeight - winHeight - 12, this.y - winHeight - 12));
                    }

                    return `position: fixed; left: ${left}px; top: ${top}px; width: ${winWidth}px; height: ${winHeight}px; bottom: auto; right: auto; z-index: 9999;`;
                }
            };
        };

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
