<?php

namespace App\Livewire;


use App\Models\Image;
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\On;

use Livewire\WithPagination;
use Livewire\WithFileUploads;



class Categories extends Component
{
    use WithPagination;
    use WithFileUploads;



    public Category $category;
    public $category_id, $upload, $savedImg, $editing, $search, $records, $pagination = 5;


    protected $rules =
    [
        'category.name' => "required|min:2|max:50|unique:categories,name"
    ];

    protected $messages = [
        'category.name.required' => 'El nombre de la categoría es obligatorio.',
        'category.name.min' => 'El nombre de la categoría debe tener al menos 2 caracteres.',
        'category.name.max' => 'El nombre de la categoría no puede tener más de 50 caracteres.',
        'category.name.unique' => 'El nombre de la categoría ya existe.',
    ];


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

            $query = Category::where('name', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc');
        } else {
            $query =  Category::orderBy('name', 'asc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }


    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('category');
        $this->category = new Category();
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
        $this->editing = false;
        $this->search = null;
        $this->dispatch('init-new');
    }



    public function Store()
    {
        try {

            $this->rules['category.name'] = $this->category->id > 0 ? "required|min:2|max:50|unique:categories,name,{$this->category->id}" : 'required|min:2|max:50|unique:categories,name';

            $this->validate($this->rules, $this->messages);

            // retrieve previous image
            $tempImg = null;
            if ($this->category->id > 0) {
                $tempImg = $this->category->image;
            }

            // save model
            $this->category->save();


            if (!empty($this->upload)) {

                if ($tempImg != null && file_exists('storage/categories/' . $tempImg->file)) {
                    unlink('storage/categories/' . $tempImg);
                }

                // delete relationship image from db
                $this->category->image()->delete();



                // generate random file name
                $fileName = uniqid() . '_.' . $this->upload->extension();
                $this->upload->storeAs('public/categories', $fileName);

                // save image record
                $img = Image::create([
                    'model_id' => $this->category->id,
                    'model_type' => 'App\Models\Category',
                    'file' => $fileName
                ]);

                // save relationship
                $this->category->image()->save($img);
            }


            $this->dispatch('noty', msg: 'CATEGORIA GUARADA CORRECTAMENTE');
            $this->resetExcept('category');
            $this->category = new Category();
            //
        } catch (\Exception $th) {
            $this->dispatch('error', msg: "Error al intentar crear la categoría \n {$th->getMessage()} ");
        }
    }

    #[On('Destroy')]
    public function Destroy($id)
    {
        try {
            $category = Category::with('image')->find($id);
            if ($category) {
                // delete all images in drive
                if (isset($category->image)) {

                    $tempImg = $category->image;
                    if ($tempImg != null && file_exists('storage/categories/' . $tempImg->file)) {
                        unlink('storage/categories/' . $tempImg->file);
                    }

                    // delete relationship image from db
                    $category->image()->delete();
                }


                // delete record from db
                $category->delete();

                $this->resetPage();


                $this->dispatch('noty', msg: 'CATEGORIA ELIMINADA');
            }
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar eliminar la categoría \n  {$th->getMessage()} ");
        }
    }
}
