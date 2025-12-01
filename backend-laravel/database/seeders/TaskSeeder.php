<?php


namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Task;
use App\Models\User;
use App\Models\Company;
use Carbon\Carbon; 

class TaskSeeder extends Seeder
{
    public function run()
    {
        $tasks = [
            [
                'company_slug' => 'aic',
                'user_email' => 'layne@aic.com',
                'title' => 'Compor nova música',
                'description' => 'Criar letra e base inicial para próximo álbum.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 7,
            ],
            [
                'company_slug' => 'aic',
                'user_email' => 'jerry@aic.com',
                'title' => 'Agendar ensaio',
                'description' => 'Marcar ensaio semanal com toda a banda.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 3,
            ],
            [
                'company_slug' => 'beat',
                'user_email' => 'john@beat.com',
                'title' => 'Gravar nova demo',
                'description' => 'Preparar ideias para novo single.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 7,
            ],
            [
                'company_slug' => 'beat',
                'user_email' => 'paul@beat.com',
                'title' => 'Revisar arranjos',
                'description' => 'Harmonizar vocais e revisar composição.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 3,
            ],
            [
                'company_slug' => 'cam',
                'user_email' => 'eric@cam.com',
                'title' => 'Turnê no Reino Unido',
                'description' => 'Finalizar planejamento da turnê nacional.',
                'status' => 'pendente',
                'priority' => 'media',
            ],
            [
                'company_slug' => 'cam',
                'user_email' => 'ginger@cam.com',
                'title' => 'Manutenção dos equipamentos',
                'description' => 'Revisar instrumentos e amplificadores.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 5,
            ],
            [
                'company_slug' => 'dth',
                'user_email' => 'chuck@dth.com',
                'title' => 'Compor riffs pesados',
                'description' => 'Criar riffs para novo álbum de metal.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 10,
            ],
            [
                'company_slug' => 'dth',
                'user_email' => 'richard@dth.com',
                'title' => 'Agendar gravação da bateria',
                'description' => 'Reservar estúdio e organizar cronograma.',
                'status' => 'pendente',
                'priority' => 'media',
                'due_days' => 3,
            ],
        ];

        foreach ($tasks as $t) {
            $company = Company::where('slug', $t['company_slug'])->first();
            $user = User::where('email', $t['user_email'])->first();

            // se não existir company ou user, pula o registro para evitar erro
            if (!$company || !$user) {
                continue;
            }

            Task::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'title' => $t['title'],
                'description' => $t['description'],
                'status' => $t['status'],
                'priority' => $t['priority'],
                'due_date' => isset($t['due_days']) ? Carbon::now()->addDays($t['due_days']) : null,
            ]);
        }
    }
}