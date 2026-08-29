<?php

namespace App\Livewire;

use App\Models\Image;
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class Categories extends Component
{
    use WithPagination;
    use WithFileUploads;

    public Category $category;
    public $category_id, $upload, $savedImg, $editing = false, $search, $records, $pagination = 10;

    // Properties for inline department creation
    public $btnCreateDept = false;
    public $newDeptName = '';
    public $newDeptType = 'local';

    public function rules()
    {
        $rules = [
            'category.name' => 'required|min:2|max:50|unique:categories,name' . ($this->category->id ? ",{$this->category->id}" : "")
        ];

        if (in_array('module_departments', config('tenant.modules', []))) {
            $rules['category.department_id'] = 'required|exists:departments,id';
        }

        return $rules;
    }

    protected $messages = [
        'category.name.required' => 'El nombre de la categoría es obligatorio.',
        'category.name.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
        'category.name.max' => 'El nombre de la categoría no puede tener más de 50 caracteres.',
        'category.name.unique' => 'El nombre de la categoría ya existe.',
        'category.department_id.required' => 'El departamento es obligatorio.',
        'category.department_id.exists' => 'El departamento seleccionado no es válido.',
    ];

    public function saveDepartment()
    {
        $this->validate([
            'newDeptName' => 'required|min:2|max:50|unique:departments,name',
            'newDeptType' => 'required|in:local,gravado',
        ], [
            'newDeptName.required' => 'El nombre del departamento es obligatorio.',
            'newDeptName.min' => 'El nombre debe tener al menos 2 caracteres.',
            'newDeptName.max' => 'El nombre no puede tener más de 50 caracteres.',
            'newDeptName.unique' => 'El departamento ya existe.',
            'newDeptType.required' => 'El tipo de reporte es obligatorio.',
            'newDeptType.in' => 'El tipo de reporte debe ser local o gravado.',
        ]);

        $dept = \App\Models\Department::create([
            'name' => trim($this->newDeptName),
            'report_type' => $this->newDeptType,
        ]);

        $this->category->department_id = $dept->id;
        $this->newDeptName = '';
        $this->newDeptType = 'local';
        $this->btnCreateDept = false;

        $this->dispatch('noty', msg: 'DEPARTAMENTO CREADO Y SELECCIONADO');
    }

    public function mount()
    {
        $this->category = new Category();
        $this->editing = false;

        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Categorías']);
    }

    public function render()
    {
        return view('livewire.categories.categories', [
            'categories' => $this->loadCategories()
        ]);
    }

    public function searching($searchText)
    {
        $this->search = trim($searchText);
    }

    public function loadCategories()
    {
        if (!empty($this->search)) {
            $this->resetPage();
            $query = Category::with('department')->where('name', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc');
        } else {
            $query = Category::with('department')->orderBy('name', 'asc');
        }

        $this->records = $query->count();
        return $query->paginate($this->pagination);
    }

    public function Add()
    {
        $this->resetValidation();
        $this->category = new Category();
        $this->upload = null;
        $this->savedImg = null;
        $this->editing = false;
        $this->dispatch('init-new');
    }

    public function Edit(Category $category)
    {
        $this->resetValidation();
        $this->category = $category;
        $this->upload = null;
        $this->savedImg = $category->picture;
        $this->editing = true;
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->category = new Category();
        $this->upload = null;
        $this->savedImg = null;
        $this->editing = false;
        $this->search = null;
        $this->dispatch('init-new');
    }

    public function Store()
    {
        try {
            $rules = $this->rules();
            $this->validate($rules, $this->messages);

            $tempImg = null;
            if ($this->category->id > 0) {
                $tempImg = $this->category->image;
            }

            $isNew = !($this->category->id > 0);
            $this->category->save();

            if (!empty($this->upload)) {
                if ($tempImg != null && !empty($tempImg->file)) {
                    Storage::disk('public')->delete('categories/' . $tempImg->file);
                    $this->category->image()->delete();
                }

                $fileName = uniqid() . '.' . $this->upload->extension();
                $this->upload->storeAs('public/categories', $fileName);

                $img = Image::create([
                    'model_id' => $this->category->id,
                    'model_type' => 'App\Models\Category',
                    'file' => $fileName
                ]);

                $this->category->image()->save($img);
            }

            $this->dispatch('noty', msg: $isNew ? 'CATEGORIA CREADA CORRECTAMENTE' : 'CATEGORIA ACTUALIZADA CORRECTAMENTE');
            
            $this->resetValidation();
            $this->category = new Category();
            $this->upload = null;
            $this->savedImg = null;
            $this->editing = false;
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $th) {
            $this->dispatch('error', msg: "Error al intentar guardar la categoría: {$th->getMessage()}");
        }
    }

    #[On('Destroy')]
    public function Destroy($id)
    {
        try {
            $category = Category::with('image')->find($id);
            if ($category) {
                if (isset($category->image) && !empty($category->image->file)) {
                    Storage::disk('public')->delete('categories/' . $category->image->file);
                    $category->image()->delete();
                }

                $category->delete();
                $this->resetPage();
                $this->dispatch('noty', msg: 'CATEGORIA ELIMINADA');
            }
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar eliminar la categoría: {$th->getMessage()}");
        }
    }
}
