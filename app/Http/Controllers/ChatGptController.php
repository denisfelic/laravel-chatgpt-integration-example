<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatGptController extends Controller
{

    public function ask(Request $request)
    {
        $input = $request->query('message');
        $response = $this->askToChatGpt($input);

        $payload = [
            'name' => $response->name,
            'due_date' => $response->due_date,
            'message' => $response->message,
        ];
        try {
            $task = new Task();
            $task->name = $payload['name'];
            $task->message = $payload['message'];
            $task->due_date = $payload['due_date'];
            $task->save();

            return [
                'error' => false,
                'message' => 'created',
                'data' => $task
            ];
        } catch (\Throwable $th) {
            //throw $th;
            return [
                'error' => true,
                'message' => 'Erro ao criar tarefa, entre em contato com o desenvolvedor!',
                'stack' => $th->getMessage(),
            ];
        }
    }

    private function askToChatGpt($userMessage)
    {

        $prompt = "Você é um assistente que recebe inputs de um usuário e devolve um json como resposta, você não deve retornar nada além do json. Você irá receber uma tarefa e talvez uma data para essa tarefa, caso elá contenha data você irá colocar em formato iso, caso contrário será apenas uma string vazia.
        Além disso você também deverá enviar uma mensagem de sucesso ou erro (pedir para o usuário enviar novamente) no parametro \"message\". Aqui um exemplo de input/output:
        Usuário: \"Me lembre amanhã de fazer compras no mercado, pode ser às 18:00h, tenho que comprar sabonete...\".
        Lembrando que você deve colocar a data baseada na data atual: 27/03/2023 18:37 - horário de brasília, e caso o usuário não informar a hora, você pode colocar por padrão às 9:00
        Importante: Você não deve retornar nada além da resposta no formato em  json
        O seu output deve seguir esse json:

        O campo message deve conter uma mensagem amigável para o usuário contendo informações da tarefa e data.
        {
            \"name\" : \"Ir ao mercado e comprar sabonete\",
            \"due_date\" : \"2023-04-28T00:00:00Z\", 
            \"message\": \"Tarefa de fazer compras no mercado criada com sucesso! Foi marcada para amanhã às 18:00h!\"
        }
        Aqui um input real:
        \"{$userMessage}\"";

        $response  = Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => 'Bearer ' . env('CHATGPT_API_KEY'),
                'Content-Type' => 'application/json',
            ])
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                "messages" =>  [["role" => "user", "content" => $prompt]]
            ]);
        return json_decode($response["choices"][0]["message"]["content"]);
    }
}
