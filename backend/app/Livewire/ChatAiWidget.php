<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Http;

class ChatAiWidget extends Component
{
    public $isOpen = false;
    public $question = '';
    public $chatHistory = [];
    public $isLoading = false;

    public function toggleChat()
    {
        $this->isOpen = !$this->isOpen;
        if ($this->isOpen && empty($this->chatHistory)) {
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => "Halo! Saya adalah Asisten AI Anda. Tanyakan apa saja seputar data penjualan, stok, atau pelanggan Anda."
            ];
        }
    }

    public function ask()
    {
        if (empty(trim($this->question))) return;

        $userQuestion = $this->question;
        $this->chatHistory[] = ['role' => 'user', 'content' => $userQuestion];
        $this->question = '';
        $this->isLoading = true;
        
        // Dispatch event to render UI immediately and then process AI
        $this->dispatch('chat-updated');
        $this->dispatch('process-ai-question');
    }

    #[On('process-ai-question')]
    public function processAiQuestion()
    {
        $lastChat = end($this->chatHistory);
        $userQuestion = $lastChat['content'] ?? '';
        
        if (empty($userQuestion)) {
            $this->isLoading = false;
            return;
        }

        try {
            // Get user's branch
            $branchId = auth()->user()->branch_id;
            
            $aiUrl = env('AI_SERVICE_URL', 'http://ai-service:8001');
            $response = Http::timeout(60)->post($aiUrl . '/api/v1/ai/ask', [
                'question' => $userQuestion,
                'branch_id' => $branchId, // Pass branch for security
                'chat_history' => $this->chatHistory, // Pass chat history for context
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $this->chatHistory[] = [
                    'role' => 'assistant',
                    'content' => $data['response'] ?? 'Maaf, saya tidak mengerti data tersebut.',
                    'sql' => $data['sql_executed'] ?? null,
                    'data' => $data['data'] ?? [],
                ];
            } else {
                $this->chatHistory[] = [
                    'role' => 'assistant',
                    'content' => 'Maaf, terjadi kesalahan saat menghubungi server AI. (Error ' . $response->status() . ')'
                ];
            }
        } catch (\Exception $e) {
            $this->chatHistory[] = [
                'role' => 'assistant',
                'content' => 'Maaf, Service AI sedang tidak aktif atau gagal terhubung.'
            ];
        }

        $this->isLoading = false;
        
        // Dispatch browser event to scroll down
        $this->dispatch('chat-updated');
    }

    public function render()
    {
        return view('livewire.chat-ai-widget');
    }
}
