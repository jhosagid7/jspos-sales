<div class="container-fluid">
    <div class="page-title">
        <div class="row">
            <div class="col-6">
                <h5><b class="txt-warning"> {{ session('pos') }}</b></h5>
            </div>
            <div class="col-6">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="#">
                            <i class="icon-location-pin"></i>
                        </a>
                    </li>
                    <li class="breadcrumb-item rfx text-warning">{{ session('map') }}</li>
                    <li class="breadcrumb-item active">{{ session('child') }}</li>
                    <li class="breadcrumb-item rest text-success">{{ session('rest') }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>