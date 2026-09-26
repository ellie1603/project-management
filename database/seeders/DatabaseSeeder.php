<?php

namespace Database\Seeders;

use App\Models\Contractor;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\ProjectPhase;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $categories = $this->seedCategories();
        $users = $this->seedUsers();
        $contractors = $this->seedContractors();

        foreach ($this->roadmap() as $definition) {
            $this->seedProject($definition, $categories, $users, $contractors);
        }
    }

    /**
     * @return array<string, ProjectCategory>
     */
    private function seedCategories(): array
    {
        $definitions = [
            'branch_expansion' => ['Branch Expansion', 'New branch and satellite offices extending member service reach'],
            'head_office' => ['Head Office Development', 'Main and head office buildings'],
            'business_building' => ['Business Building', 'Income-generating cooperative buildings'],
            'branch_repairs' => ['Branch Repairs', 'Repair and renovation of existing branch offices'],
        ];

        return collect($definitions)->map(fn (array $data): ProjectCategory => ProjectCategory::firstOrCreate(
            ['name' => $data[0]],
            ['description' => $data[1], 'status' => 'active'],
        ))->all();
    }

    /**
     * @return array<string, User>
     */
    private function seedUsers(): array
    {
        $accounts = [
            'admin' => ['admin@bmpc.test', 'System Administrator', 'admin', null],
            'juan' => ['juan@bmpc.test', 'Juan Dela Cruz', 'project_personnel', 'Contractor'],
            'maria' => ['maria@bmpc.test', 'Maria Santos', 'project_personnel', 'Foreman'],
            'pedro' => ['pedro@bmpc.test', 'Pedro Reyes', 'project_personnel', 'Branch Manager'],
            'finance' => ['finance@bmpc.test', 'Finance Officer', 'finance_accounting', null],
        ];

        return collect($accounts)->map(fn (array $data): User => User::query()->updateOrCreate(
            ['email' => $data[0]],
            [
                'name' => $data[1],
                'password' => Hash::make('password123'),
                'role' => $data[2],
                'position_type' => $data[3],
                'is_active' => true,
            ],
        ))->all();
    }

    /**
     * @return array<string, Contractor>
     */
    private function seedContractors(): array
    {
        $contractors = [
            'aklan' => ['Aklan Premier Builders', 'Ana Lim', 'Kalibo, Aklan', '0917-555-0101', 'contact@aklanpremier.test', 'DTI-10421'],
            'panay' => ['Panay Construction Supply', 'Mark Tan', 'Iloilo City, Iloilo', '0917-555-0102', 'sales@panaysupply.test', 'DTI-10987'],
            'barbaza' => ['Barbaza Engineering Works', 'Liza Cordero', 'Barbaza, Antique', '0917-555-0103', 'office@barbazaengineering.test', 'DTI-11354'],
            'antique' => ['Antique Builders Corp', 'Ramon Villanueva', 'San Jose de Buenavista, Antique', '0917-555-0104', 'projects@antiquebuilders.test', 'DTI-11802'],
            'capiz' => ['Capiz Interior Fit-Out Services', 'Grace Uy', 'Roxas City, Capiz', '0917-555-0105', 'hello@capizfitout.test', 'DTI-12236'],
        ];

        return collect($contractors)->map(fn (array $data): Contractor => Contractor::firstOrCreate(
            ['name' => $data[0]],
            [
                'contact_person' => $data[1],
                'contact_number' => $data[3],
                'email' => $data[4],
                'address' => $data[2],
                'registration_information' => $data[5],
                'status' => 'active',
                'remarks' => null,
            ],
        ))->all();
    }

    /**
     * @param  array<string, mixed>  $d
     * @param  array<string, ProjectCategory>  $categories
     * @param  array<string, User>  $users
     * @param  array<string, Contractor>  $contractors
     */
    private function seedProject(array $d, array $categories, array $users, array $contractors): void
    {
        $project = Project::query()->firstOrCreate(
            ['project_code' => $d['code']],
            [
                'title' => $d['title'],
                'description' => $d['description'],
                'category_id' => $categories[$d['category']]->id,
                'project_type' => $d['type'],
                'location' => $d['location'],
                'objective' => $d['objective'],
                'approved_budget' => $d['budget'],
                'planned_start_date' => $d['start'],
                'target_completion_date' => $d['target'],
                'actual_start_date' => $d['actual_start'] ?? null,
                'actual_completion_date' => $d['actual_completion'] ?? null,
                'status' => $d['status'],
                'remarks' => $d['remarks'] ?? null,
                'created_by' => $users['admin']->id,
            ],
        );

        foreach ($d['personnel'] ?? [] as [$userKey, $position, $responsibility]) {
            $project->assignPersonnel($users[$userKey], $position, $responsibility);
        }

        $phasesDone = $d['phases_done'] ?? 0;
        $this->seedPhases($project, $phasesDone, markNextInProgress: $d['status'] === 'Ongoing');

        foreach ($d['progress'] ?? [] as $entry) {
            $project->progress()->firstOrCreate(
                ['project_id' => $project->id, 'progress_date' => $entry['date']],
                [
                    'user_id' => $users[$entry['by']]->id,
                    'phase_id' => $project->phases()->where('sequence', min($entry['phases'] + 1, count(Project::DEFAULT_PHASES)))->value('id'),
                    'progress_percentage' => (int) round($entry['phases'] / count(Project::DEFAULT_PHASES) * 100),
                    'accomplishments' => $entry['accomplishments'],
                    'activities_completed' => $entry['completed'] ?? null,
                    'activities_remaining' => $entry['remaining'] ?? null,
                    'issues' => $entry['issues'] ?? null,
                ],
            );
        }

        $expenses = [];
        foreach ($d['expenses'] ?? [] as $key => [$date, $category, $description, $amount, $payee]) {
            $expenses[$key] = $project->expenses()->firstOrCreate(
                ['project_id' => $project->id, 'expense_date' => $date, 'description' => $description],
                [
                    'category' => $category,
                    'amount' => $amount,
                    'payee' => $payee,
                    'reference_number' => 'OR-'.str_replace('-', '', $date).'-'.str_pad((string) ($project->id * 10 + count($expenses)), 3, '0', STR_PAD_LEFT),
                    'created_by' => $users['finance']->id,
                ],
            );
        }

        foreach ($d['contractors'] ?? [] as [$contractorKey, $role, $amount]) {
            $project->contractors()->syncWithoutDetaching([
                $contractors[$contractorKey]->id => [
                    'role' => $role,
                    'contract_amount' => $amount,
                    'start_date' => $d['actual_start'] ?? $d['start'],
                    'end_date' => $d['actual_completion'] ?? null,
                ],
            ]);
        }

        foreach ($d['quotations'] ?? [] as [$contractorKey, $amount, $date, $remarks]) {
            $project->quotations()->firstOrCreate(
                ['project_id' => $project->id, 'contractor_id' => $contractors[$contractorKey]->id],
                ['quotation_amount' => $amount, 'quotation_date' => $date, 'remarks' => $remarks],
            );
        }

        foreach ($d['budget_requests'] ?? [] as $request) {
            $reviewed = $request['status'] !== 'Pending';

            $project->budgetRequests()->firstOrCreate(
                ['project_id' => $project->id, 'purpose' => $request['purpose']],
                [
                    'requested_by' => $users[$request['by']]->id,
                    'amount' => $request['amount'],
                    'description' => $request['description'],
                    'status' => $request['status'],
                    'reviewed_by' => $reviewed ? $users['finance']->id : null,
                    'reviewed_at' => $reviewed ? $request['reviewed_at'] : null,
                    'remarks' => $request['remarks'] ?? null,
                    'expense_id' => isset($request['expense']) ? $expenses[$request['expense']]->id : null,
                ],
            );
        }
    }

    /**
     * Seed the standard construction stage template, marking the first
     * $completedCount stages Completed (and, for active work, the next one In
     * Progress) so the derived completion percentage matches the scenario.
     */
    private function seedPhases(Project $project, int $completedCount, bool $markNextInProgress = true): void
    {
        foreach (Project::DEFAULT_PHASES as $index => $name) {
            $sequence = $index + 1;
            $status = match (true) {
                $sequence <= $completedCount => 'Completed',
                $sequence === $completedCount + 1 && $markNextInProgress => 'In Progress',
                default => 'Not Started',
            };

            ProjectPhase::query()->firstOrCreate(
                ['project_id' => $project->id, 'sequence' => $sequence],
                ['name' => $name, 'status' => $status],
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function roadmap(): array
    {
        return [
            ...$this->branchOffices(),
            ...$this->satelliteOffices(),
            $this->headOffice(),
            $this->dormitory(),
            ...$this->branchRepairs(),
        ];
    }

    /**
     * Roadmap item 1 — New Branch Office.
     *
     * @return array<int, array<string, mixed>>
     */
    private function branchOffices(): array
    {
        return [
            [
                'code' => 'BMPC-PRJ-2026-0001',
                'title' => 'New Branch Office — Altavas',
                'description' => 'Construction of a new BMPC branch office in Altavas, Aklan.',
                'category' => 'branch_expansion',
                'type' => 'Construction',
                'location' => 'Altavas, Aklan',
                'objective' => 'Extend cooperative services to members in northern Aklan.',
                'budget' => 4500000,
                'start' => '2026-01-05',
                'target' => '2026-06-30',
                'actual_start' => '2026-01-05',
                'actual_completion' => '2026-06-26',
                'status' => 'Completed',
                'remarks' => 'Turned over ahead of the end-June target.',
                'personnel' => [['juan', 'Contractor', 'Site execution and turnover']],
                'phases_done' => 5,
                'progress' => [
                    ['date' => '2026-02-27', 'by' => 'juan', 'phases' => 2, 'accomplishments' => 'Foundation and ground-floor columns completed.', 'completed' => 'Site clearing, excavation, footings, columns.', 'remaining' => 'Second floor slab, roofing, finishing.'],
                    ['date' => '2026-04-24', 'by' => 'juan', 'phases' => 3, 'accomplishments' => 'Structure and roofing completed.'],
                    ['date' => '2026-06-26', 'by' => 'juan', 'phases' => 5, 'accomplishments' => 'Finishing, electrical, and final inspection completed. Building turned over.'],
                ],
                'expenses' => [
                    ['2026-01-20', 'Materials', 'Cement, rebar, and aggregates', 1150000, 'Panay Construction Supply'],
                    ['2026-03-16', 'Labor', 'Structural works — progress billing 1', 1080000, 'Aklan Premier Builders'],
                    ['2026-05-18', 'Materials', 'Roofing and finishing materials', 640000, 'Panay Construction Supply'],
                    ['2026-06-29', 'Labor', 'Finishing works — final billing', 550000, 'Aklan Premier Builders'],
                ],
                'contractors' => [['aklan', 'Main Contractor', 3950000]],
            ],
            [
                'code' => 'BMPC-PRJ-2026-0002',
                'title' => 'New Branch Office — Second Site',
                'description' => 'Construction of a second new branch office; site to be identified.',
                'category' => 'branch_expansion',
                'type' => 'Construction',
                'location' => 'To be identified',
                'objective' => 'Open a second new branch in the July–December roadmap window.',
                'budget' => 4500000,
                'start' => '2026-07-06',
                'target' => '2026-12-31',
                'status' => 'On Hold',
                'remarks' => 'Site identification in progress; construction starts once the lot is secured.',
                'personnel' => [['pedro', 'Branch Manager', 'Site identification and lot acquisition']],
            ],
        ];
    }

    /**
     * Roadmap item 2 — New Satellite Office.
     *
     * @return array<int, array<string, mixed>>
     */
    private function satelliteOffices(): array
    {
        return [
            [
                'code' => 'BMPC-PRJ-2026-0003',
                'title' => 'New Satellite Office — Roxas City',
                'description' => 'Satellite office in Roxas City, Capiz, set up in a rented space with interior fit-out construction.',
                'category' => 'branch_expansion',
                'type' => 'Construction / Rent Space',
                'location' => 'Roxas City, Capiz',
                'objective' => 'Serve Capiz members from a leased, fitted-out satellite office.',
                'budget' => 1800000,
                'start' => '2026-01-05',
                'target' => '2026-06-30',
                'actual_start' => '2026-01-12',
                'actual_completion' => '2026-06-24',
                'status' => 'Completed',
                'remarks' => 'Completed; fit-out costs ran close to the approved budget.',
                'personnel' => [['maria', 'Foreman', 'Fit-out supervision']],
                'phases_done' => 5,
                'progress' => [
                    ['date' => '2026-03-20', 'by' => 'maria', 'phases' => 3, 'accomplishments' => 'Lease signed; partitions and ceiling works completed.'],
                    ['date' => '2026-06-24', 'by' => 'maria', 'phases' => 5, 'accomplishments' => 'Fit-out completed and office opened to members.'],
                ],
                'expenses' => [
                    ['2026-01-15', 'Rent', 'Lease deposit and advance rental', 420000, 'Roxas City property lessor'],
                    ['2026-03-09', 'Labor', 'Interior fit-out works', 780000, 'Capiz Interior Fit-Out Services'],
                    ['2026-05-25', 'Equipment', 'Teller counters, vault, and IT equipment', 456000, 'Capiz Interior Fit-Out Services'],
                ],
                'contractors' => [['capiz', 'Fit-Out Contractor', 1236000]],
            ],
            [
                'code' => 'BMPC-PRJ-2026-0004',
                'title' => 'New Satellite Office — Second Site',
                'description' => 'Second satellite office via construction or rented space; site to be identified.',
                'category' => 'branch_expansion',
                'type' => 'Construction / Rent Space',
                'location' => 'To be identified',
                'objective' => 'Open a second satellite office in the July–December roadmap window.',
                'budget' => 1800000,
                'start' => '2026-07-06',
                'target' => '2026-12-31',
                'status' => 'Registered',
                'remarks' => 'Awaiting site identification and personnel assignment.',
            ],
        ];
    }

    /**
     * Roadmap item 3 — Main & Head Office New Building.
     *
     * @return array<string, mixed>
     */
    private function headOffice(): array
    {
        return [
            'code' => 'BMPC-PRJ-2026-0005',
            'title' => 'Main & Head Office New Building',
            'description' => 'Construction of the new BMPC main and head office building in Cubay, Barbaza.',
            'category' => 'head_office',
            'type' => 'Construction',
            'location' => 'Cubay, Barbaza, Antique',
            'objective' => 'Consolidate head office operations in a purpose-built facility.',
            'budget' => 12000000,
            'start' => '2026-04-06',
            'target' => '2026-06-30',
            'actual_start' => '2026-04-06',
            'status' => 'Ongoing',
            'remarks' => 'Past the June target; unstable soil required additional foundation work.',
            'personnel' => [
                ['juan', 'Contractor', 'Structural works'],
                ['maria', 'Foreman', 'Day-to-day site supervision'],
            ],
            'phases_done' => 3,
            'progress' => [
                ['date' => '2026-05-15', 'by' => 'maria', 'phases' => 1, 'accomplishments' => 'Excavation completed.', 'issues' => 'Soft soil found at the north wing; soil test requested.'],
                ['date' => '2026-07-17', 'by' => 'juan', 'phases' => 2, 'accomplishments' => 'Foundation completed with additional piling.', 'issues' => 'Piling added two months to the schedule.'],
                ['date' => '2026-09-18', 'by' => 'maria', 'phases' => 3, 'accomplishments' => 'Second-floor structure completed.', 'completed' => 'Columns, beams, and slabs up to the second floor.', 'remaining' => 'Roofing, MEP installation, finishing.', 'issues' => 'Expenses have passed the approved budget.'],
            ],
            'expenses' => [
                'materials' => ['2026-04-20', 'Materials', 'Structural steel and cement', 4300000, 'Panay Construction Supply'],
                'piling' => ['2026-06-08', 'Civil Works', 'Additional piling for unstable soil', 2650000, 'Barbaza Engineering Works'],
                'billing1' => ['2026-07-27', 'Labor', 'Structural works — progress billing 1', 3200000, 'Barbaza Engineering Works'],
                'billing2' => ['2026-09-14', 'Labor', 'Structural works — progress billing 2', 2500000, 'Barbaza Engineering Works'],
            ],
            'contractors' => [['barbaza', 'Main Contractor', 10800000]],
            'budget_requests' => [
                [
                    'purpose' => 'Roofing materials',
                    'by' => 'juan',
                    'amount' => 850000,
                    'description' => 'Roofing sheets, trusses, and insulation for the main building.',
                    'status' => 'Pending',
                ],
            ],
        ];
    }

    /**
     * Roadmap item 4 — Dormitory Business Building. No provider history yet, so
     * three quotations are on file for management to compare.
     *
     * @return array<string, mixed>
     */
    private function dormitory(): array
    {
        return [
            'code' => 'BMPC-PRJ-2026-0006',
            'title' => 'Dormitory Business Building',
            'description' => 'Construction of a dormitory business building in Sibalom, Antique.',
            'category' => 'business_building',
            'type' => 'Construction',
            'location' => 'Sibalom, Antique',
            'objective' => 'Generate rental income for the cooperative from student and worker housing.',
            'budget' => 6500000,
            'start' => '2026-10-05',
            'target' => '2026-12-31',
            'status' => 'Registered',
            'remarks' => 'Three provider quotations on file for management review.',
            'personnel' => [['maria', 'Foreman', 'Pre-construction planning']],
            'quotations' => [
                ['antique', 6180000, '2026-08-24', 'Includes site development.'],
                ['aklan', 6350000, '2026-08-27', 'Excludes perimeter fence.'],
                ['barbaza', 6020000, '2026-09-02', 'Ninety-day completion commitment.'],
            ],
        ];
    }

    /**
     * Roadmap item 5 — Branch Repairs across twelve branches. The roadmap lists
     * the branches without dates, so they are scheduled in quarterly batches.
     *
     * @return array<int, array<string, mixed>>
     */
    private function branchRepairs(): array
    {
        $repair = fn (int $number, string $branch, string $location, int $budget, string $start, string $target, string $status, array $extra = []): array => [
            'code' => sprintf('BMPC-PRJ-2026-%04d', $number),
            'title' => "Branch Repair — {$branch}",
            'description' => "Repair and renovation of the {$branch} branch office.",
            'category' => 'branch_repairs',
            'type' => 'Repair & Renovation',
            'location' => $location,
            'objective' => 'Restore the branch office to safe, serviceable condition.',
            'budget' => $budget,
            'start' => $start,
            'target' => $target,
            'status' => $status,
            ...$extra,
        ];

        $completed = fn (string $finished, string $by, string $contractor, array $expenses): array => [
            'actual_completion' => $finished,
            'phases_done' => 5,
            'personnel' => [[$by, $by === 'pedro' ? 'Branch Manager' : ($by === 'maria' ? 'Foreman' : 'Contractor'), 'Repair supervision']],
            'progress' => [['date' => $finished, 'by' => $by, 'phases' => 5, 'accomplishments' => 'Repairs completed and inspected.']],
            'expenses' => $expenses,
            'contractors' => [[$contractor, 'Repair Contractor', array_sum(array_column($expenses, 3))]],
        ];

        return [
            // Q1 batch — completed
            $repair(7, 'Culasi', 'Culasi, Antique', 280000, '2026-01-12', '2026-03-27', 'Completed', ['actual_start' => '2026-01-12'] + $completed('2026-03-20', 'pedro', 'antique', [
                ['2026-02-02', 'Materials', 'Roofing replacement materials', 118000, 'Panay Construction Supply'],
                ['2026-03-20', 'Labor', 'Roof and ceiling repair', 96000, 'Antique Builders Corp'],
            ])),
            $repair(8, 'Sibalom', 'Sibalom, Antique', 220000, '2026-01-12', '2026-03-27', 'Completed', ['actual_start' => '2026-01-19'] + $completed('2026-03-25', 'maria', 'antique', [
                ['2026-02-09', 'Materials', 'Plumbing and comfort room fixtures', 74000, 'Panay Construction Supply'],
                ['2026-03-25', 'Labor', 'Plumbing and repainting works', 88000, 'Antique Builders Corp'],
            ])),
            $repair(9, 'San Jose', 'San Jose de Buenavista, Antique', 350000, '2026-01-12', '2026-03-27', 'Completed', ['actual_start' => '2026-01-12'] + $completed('2026-03-27', 'pedro', 'antique', [
                ['2026-02-16', 'Materials', 'Electrical rewiring materials', 142000, 'Panay Construction Supply'],
                ['2026-03-27', 'Labor', 'Electrical and flooring works', 131000, 'Antique Builders Corp'],
            ])),

            // Q2 batch
            $repair(10, 'Balasan', 'Balasan, Iloilo', 300000, '2026-04-06', '2026-06-26', 'Completed', ['actual_start' => '2026-04-06'] + $completed('2026-06-19', 'juan', 'panay', [
                ['2026-04-27', 'Materials', 'Windows, doors, and security grills', 126000, 'Panay Construction Supply'],
                ['2026-06-19', 'Labor', 'Façade and security upgrades', 104000, 'Panay Construction Supply'],
            ])),
            $repair(11, 'Barotac Viejo', 'Barotac Viejo, Iloilo', 260000, '2026-04-06', '2026-06-26', 'Cancelled', [
                'remarks' => 'Cancelled: branch scheduled for relocation, so repairs were withdrawn.',
            ]),
            $repair(12, 'Caticlan', 'Caticlan, Malay, Aklan', 420000, '2026-04-06', '2026-06-26', 'Completed', ['actual_start' => '2026-04-13'] + $completed('2026-06-24', 'juan', 'aklan', [
                ['2026-05-04', 'Materials', 'Waterproofing and roofing materials', 168000, 'Panay Construction Supply'],
                ['2026-06-24', 'Labor', 'Roof waterproofing and ceiling repair', 142000, 'Aklan Premier Builders'],
            ])),

            // Q3 batch — in progress
            $repair(13, 'Calinog', 'Calinog, Iloilo', 380000, '2026-07-06', '2026-09-30', 'Ongoing', [
                'actual_start' => '2026-07-06',
                'remarks' => 'Nearing its approved budget ceiling.',
                'personnel' => [['juan', 'Contractor', 'Repair works']],
                'phases_done' => 3,
                'progress' => [
                    ['date' => '2026-08-14', 'by' => 'juan', 'phases' => 2, 'accomplishments' => 'Roof framing replaced.'],
                    ['date' => '2026-09-16', 'by' => 'juan', 'phases' => 3, 'accomplishments' => 'Ceiling and wall repairs completed.', 'remaining' => 'Repainting and electrical fixtures.'],
                ],
                'expenses' => [
                    'lumber' => ['2026-07-20', 'Materials', 'Lumber and roofing sheets', 168000, 'Panay Construction Supply'],
                    'labor' => ['2026-08-31', 'Labor', 'Roof and ceiling works', 112000, 'Panay Construction Supply'],
                    'paint' => ['2026-09-10', 'Budget Request', 'Paint and electrical fixtures', 55000, 'Panay Construction Supply'],
                ],
                'contractors' => [['panay', 'Repair Contractor', 330000]],
                'budget_requests' => [
                    [
                        'purpose' => 'Paint and electrical fixtures',
                        'by' => 'juan',
                        'amount' => 55000,
                        'description' => 'Repainting materials and replacement light fixtures.',
                        'status' => 'Approved',
                        'reviewed_at' => '2026-09-09 10:00:00',
                        'expense' => 'paint',
                    ],
                ],
            ]),
            $repair(14, 'Molo', 'Molo, Iloilo City', 320000, '2026-07-06', '2026-09-30', 'Ongoing', [
                'actual_start' => '2026-07-20',
                'remarks' => 'Started two weeks late; behind pace.',
                'personnel' => [['maria', 'Foreman', 'Repair supervision']],
                'phases_done' => 2,
                'progress' => [
                    ['date' => '2026-09-04', 'by' => 'maria', 'phases' => 2, 'accomplishments' => 'Flooring demolition and subfloor repair completed.', 'issues' => 'Tile delivery delayed by supplier.'],
                ],
                'expenses' => [
                    ['2026-07-27', 'Materials', 'Floor tiles and adhesives', 96000, 'Panay Construction Supply'],
                ],
                'budget_requests' => [
                    [
                        'purpose' => 'Replacement tiles',
                        'by' => 'maria',
                        'amount' => 40000,
                        'description' => 'Replacement for a damaged tile delivery.',
                        'status' => 'Rejected',
                        'reviewed_at' => '2026-09-08 14:00:00',
                        'remarks' => 'Claim against the supplier first; resubmit if the claim is denied.',
                    ],
                ],
            ]),
            $repair(15, 'Kalibo', 'Kalibo, Aklan', 450000, '2026-08-03', '2026-10-30', 'Ongoing', [
                'actual_start' => '2026-08-03',
                'personnel' => [['pedro', 'Branch Manager', 'Coordination with branch operations']],
                'phases_done' => 2,
                'progress' => [
                    ['date' => '2026-09-11', 'by' => 'pedro', 'phases' => 2, 'accomplishments' => 'Electrical rewiring completed.', 'remaining' => 'Ceiling, repainting, signage.'],
                ],
                'expenses' => [
                    ['2026-08-10', 'Materials', 'Electrical wiring and panel board', 128000, 'Panay Construction Supply'],
                    ['2026-09-07', 'Labor', 'Electrical works', 74000, 'Aklan Premier Builders'],
                ],
                'contractors' => [['aklan', 'Repair Contractor', 390000]],
                'budget_requests' => [
                    [
                        'purpose' => 'Branch signage',
                        'by' => 'pedro',
                        'amount' => 35000,
                        'description' => 'Replacement exterior signage.',
                        'status' => 'Pending',
                    ],
                ],
            ]),

            // Q4 batch — not yet started
            $repair(16, 'Sara', 'Sara, Iloilo', 240000, '2026-10-05', '2026-12-18', 'Registered'),
            $repair(17, 'President Roxas', 'President Roxas, Capiz', 260000, '2026-10-05', '2026-12-18', 'Registered'),
            $repair(18, 'Guimaras', 'Jordan, Guimaras', 300000, '2026-10-05', '2026-12-18', 'Registered'),
        ];
    }
}
