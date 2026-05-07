<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\StatusUpdateRequest;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OptionController extends BaseController
{
    protected $modelMap = [
        'province' => [
            'model' => \App\Models\Data\Province::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'district' => [
            'model' => \App\Models\Data\District::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'municipality' => [
            'model' => \App\Models\Data\Municipality::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'ward' => [
            'model' => \App\Models\Data\Ward::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'roadtype' => [
            'model' => \App\Models\Data\RoadType::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'roadcondition' => [
            'model' => \App\Models\Data\RoadCondition::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'unit' => [
            'model' => \App\Models\Data\Unit::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'propertytype' => [
            'model' => \App\Models\Data\PropertyType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'propertystatus' => [
            'model' => \App\Models\Data\PropertyStatus::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'listingtype' => [
            'model' => \App\Models\Data\ListingType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'housetype' => [
            'model' => \App\Models\Data\HouseType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'rooftype' => [
            'model' => \App\Models\Data\RoofType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'constructionstatus' => [
            'model' => \App\Models\Data\ConstructionStatus::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'watersource' => [
            'model' => \App\Models\Data\WaterSource::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'sewagetype' => [
            'model' => \App\Models\Data\SewageType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'propertyface' => [
            'model' => \App\Models\Data\PropertyFace::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'contructionstatus' => [
            'model' => \App\Models\Data\ConstructionStatus::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'province' => [
            'model' => \App\Models\Data\Province::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'district' => [
            'model' => \App\Models\Data\District::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'municipality' => [
            'model' => \App\Models\Data\Municipality::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'ward' => [
            'model' => \App\Models\Data\Ward::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'measureUnit' => [
            'model' => \App\Models\Data\MeasureUnit::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'propertyCategory' => [
            'model' => \App\Models\Data\PropertyCategory::class,
            'validation' => [
                'store' => ['name' => 'required|string|max:255'],
                'update' => ['name' => 'sometimes|required|string|max:255']
            ]
        ],
        'furnishing' => [
            'model' => \App\Models\Data\Furnishing::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'constructionStatus' => [
            'model' => \App\Models\Data\ConstructionStatus::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'parkingType' => [
            'model' => \App\Models\Data\ParkingType::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'amenities' => [
            'model' => \App\Models\Data\Amenities::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'contact_method' => [
            'model' => \App\Models\Data\ContactMethod::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'status' => [
            'model' => \App\Models\Data\BlogStatus::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'request_type' => [
            'model' => \App\Models\Data\RequestTypes::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],
        'category' => [
            'model' => \App\Models\Data\Category::class,
            'validation' => [
                'store' => ['label' => 'required|string|max:255'],
                'update' => ['label' => 'sometimes|required|string|max:255']
            ]
        ],

    ];

    protected array $allowedFilters = [];

    /**
     * Fetch options for dropdown
     */
    public function fetchOption(Request $request)
    {
        $limit = $request->get('limit');
        if (empty($limit) || $limit == 0) {
            $limit = $request->header('X-Limit-No') ?? 10;
        }
        $limit = is_numeric($limit) ? $limit : 10;
        $page  = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;
        $validator = Validator::make($request->all(), [
            'dropdownfor' => 'required|string'
        ]);
        $isStatus = $request->get('isStatus');

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $type = strtolower($request->get('dropdownfor'));

        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid dropdown type.', null, 400);
        }

        $modelConfig = $this->modelMap[$type];
        $model = $modelConfig['model'];
        $displayField = $this->displayFieldForType($type);



        if ($type === 'category') {
            $query = $model::select('*')->with('creator')->with('children')->where('parent_id', null);
        } else {
            $query = $model::select('*')->with('creator');
        }

        // Apply allowed filters
        $this->applyFilters($query, $request, $type);

        // Optional: Add ordering
        if ($isStatus !== null) {
            $query->where('is_status', $isStatus);
        }
        if ($request->filled('title')) {
            $query->where($displayField, 'LIKE', '%' . $request->input('title') . '%');
        }
        $query->orderBy($this->defaultSortColumnForType($type));

        $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'message' => 'sucessfully interview list',
            'data' => $paginatedResults->items(),
            'normalized_data' => collect($paginatedResults->items())
                ->map(fn ($record) => $this->normalizeOptionRecord($record, $type))
                ->values(),
            'meta' => [
                'type' => $type,
                'display_field' => $displayField,
                'default_sort' => $this->defaultSortColumnForType($type),
            ],
            'pagination' => [
                'total' => $paginatedResults->total(), // Total records
                'per_page' => $paginatedResults->perPage(), // Items per page
                'current_page' => $paginatedResults->currentPage(), // Current page
                'last_page' => $paginatedResults->lastPage(), // Last page number
            ],
        ], 200);
    }

    /**
     * Store a new option
     */
    public function store(Request $request)
    {
        // Fixed validation - removed quotes around field names
        $validator = Validator::make($request->all(), [
            'dropdownfor' => 'required|string',
            'label' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error', $validator->errors(), 422);
        }

        $type = strtolower($request->input('dropdownfor'));

        // Check if model type exists
        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid model type.', null, 400);
        }

        $modelConfig = $this->modelMap[$type];
        $model = $modelConfig['model'];

        $data = $request->except('dropdownfor');

        if (isset($data['parentId'])) {
            $data['parent_id'] = $data['parentId'];
            unset($data['parentId']);
        }

        try {
            $item = $model::create($data);
            return $this->sendResponse($item, ucfirst($type) . ' created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Insert failed', $e->getMessage(), 500);
        }
    }
    /**
     * Update an existing option
     */
    public function update(Request $request, $id)
    {
        $type = strtolower($request->get('dropdownfor'));

        if (!isset($this->modelMap[$type])) {
            return $this->sendError($type, $type, 400);
        }

        $modelConfig = $this->modelMap[$type];
        $modelClass = $modelConfig['model'];

        try {
            $item = $modelClass::find($id);

            if (!$item) {
                return $this->sendError('Item not found.', null, 404);
            }
            $data = $request->except('dropdownfor');
            $item->update($data);

            return $this->sendResponse($item, 'Option updated successfully');
        } catch (\Exception $e) {

            return $this->sendError('Update failed', $e->getMessage(), 500);
        }
    }
    /**
     * Delete an option
     */
    public function destroy($id, $type)
    {
        try {
            $type = strtolower($type);
            $model = $this->modelMap[$type]['model'];
            $item = $model::find($id);

            if (!$item) {
                return $this->sendError('Item not found.', null, 404);
            }

            $item->delete();

            return $this->sendResponse(null, 'Option deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Delete failed', $e->getMessage(), 500);
        }
    }

    /**
     * Show available option types
     */
    public function showOption()
    {
        return $this->sendResponse($this->availableOptionTypes(), 'Available options retrieved successfully');
    }

    /**
     * Apply allowed filters to query
     */
    protected function applyFilters($query, Request $request, string $type)
    {
        $allowedFilters = $this->allowedFilters[$type] ?? [];

        foreach ($allowedFilters as $filter) {
            if ($request->has($filter)) {
                $query->where($filter, $request->input($filter));
            }
        }
    }
    public function updateStatus($id, $request)
    {
        $type = strtolower($request->get('dropdownfor'));
        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid dropdown type.', null, 400);
        }

        $modelConfig = $this->modelMap[$type];
        $model = $modelConfig['model'];
        $vacancy = $model::findOrFail($id);
        $vacancy->isStatus = $request->get('isStatus');

        $vacancy->update();
        return $vacancy;
    }


    public function optionMenu()
    {
        $menuData = collect($this->availableOptionTypes())
            ->map(fn (array $type) => [
                'name' => $type['label'],
                'type' => $type['type'],
                'display_field' => $type['display_field'],
            ])
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'data' => $menuData,
            'message' => 'Menu titles have been successfully listed',
        ], 200);
    }

    public function getOptionById($id, Request $request)
    {
        $type = strtolower($request->get('dropdownfor'));

        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid dropdown type.', null, 400);
        }

        $modelClass = $this->modelMap[$type]['model'];

        try {
            $option = app($modelClass)->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $option,
                'normalized' => $this->normalizeOptionRecord($option, $type),
                'message' => 'Option has been successfully retrieved',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Option not found.', null, 404);
        }
    }

    public function types()
    {
        return response()->json([
            'success' => true,
            'data' => $this->availableOptionTypes(),
            'message' => 'Option types retrieved successfully',
        ]);
    }

    public function catalog(Request $request, string $type)
    {
        $type = strtolower($type);

        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid dropdown type.', null, 400);
        }

        $model = $this->modelMap[$type]['model'];
        $displayField = $this->displayFieldForType($type);
        $limit = is_numeric($request->get('limit')) ? (int) $request->get('limit') : 25;
        $page = is_numeric($request->get('page')) ? (int) $request->get('page') : 1;

        $query = $model::query();

        if ($request->filled('search')) {
            $query->where($displayField, 'LIKE', '%' . $request->get('search') . '%');
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        if ($request->filled('is_status')) {
            $query->where('is_status', (int) $request->boolean('is_status'));
        }

        $query->orderBy($this->defaultSortColumnForType($type));

        $paginatedResults = $query->paginate($limit, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => collect($paginatedResults->items())
                ->map(fn ($record) => $this->normalizeOptionRecord($record, $type))
                ->values(),
            'meta' => [
                'type' => $type,
                'display_field' => $displayField,
                'default_sort' => $this->defaultSortColumnForType($type),
            ],
            'pagination' => [
                'total' => $paginatedResults->total(),
                'per_page' => $paginatedResults->perPage(),
                'current_page' => $paginatedResults->currentPage(),
                'last_page' => $paginatedResults->lastPage(),
            ],
            'message' => 'Option catalog retrieved successfully',
        ]);
    }

    public function getDropdownOptions($slug, $module = null)
    {

        $type = strtolower($slug);

        if (!isset($this->modelMap[$type])) {
            return $this->sendError('Invalid dropdown type.', null, 400);
        }

        $modelConfig = $this->modelMap[$type];
        $model = $modelConfig['model'];

        try {
            $query = $model::query()
                ->orderBy($this->defaultSortColumnForType($type));

            $items = $query->get()
                ->map(fn ($record) => $this->normalizeOptionRecord($record, $type))
                ->map(fn (array $record) => [
                    'id' => $record['id'],
                    'label' => $record['label'],
                    'value' => $record['value'],
                    'slug' => $record['slug'],
                    'type' => $record['type'],
                    'is_status' => $record['is_status'],
                ])
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'sucessfully interview list',
                'data' => $items,
                'meta' => [
                    'type' => $type,
                    'display_field' => $this->displayFieldForType($type),
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->sendError('Server Error', 'Failed to fetch options', 500);
        }
    }

    public function getAllOptions()
    {
        try {
            $allOptions = [];

            foreach ($this->modelMap as $key => $config) {
                $model = $config['model'];

                $records = $model::all();

                $allOptions[$key] = [
                    'raw' => $records,
                    'normalized' => $records->map(fn ($record) => $this->normalizeOptionRecord($record, $key))->values(),
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $allOptions,
                'message' => 'All options retrieved successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving options',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function availableOptionTypes(): array
    {
        return collect($this->modelMap)
            ->map(function (array $config, string $type) {
                return [
                    'type' => $type,
                    'label' => Str::headline($type),
                    'display_field' => $this->displayFieldForType($type),
                    'default_sort' => $this->defaultSortColumnForType($type),
                ];
            })
            ->values()
            ->all();
    }

    private function displayFieldForType(string $type): string
    {
        $storeRules = $this->modelMap[$type]['validation']['store'] ?? [];

        if (array_key_exists('label', $storeRules)) {
            return 'label';
        }

        if (array_key_exists('name', $storeRules)) {
            return 'name';
        }

        return 'name';
    }

    private function defaultSortColumnForType(string $type): string
    {
        return $this->displayFieldForType($type);
    }

    private function normalizeOptionRecord($record, string $type): array
    {
        $displayField = $this->displayFieldForType($type);
        $label = (string) data_get($record, $displayField, '');
        $slug = data_get($record, 'slug');

        return [
            'id' => data_get($record, 'id'),
            'label' => $label,
            'name' => $label,
            'value' => data_get($record, 'id'),
            'slug' => $slug ?: Str::slug($label),
            'type' => $type,
            'display_field' => $displayField,
            'is_status' => data_get($record, 'is_status'),
            'raw' => method_exists($record, 'toArray') ? $record->toArray() : (array) $record,
        ];
    }
}
