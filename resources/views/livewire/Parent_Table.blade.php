<button class="btn bg-primary text-on-primary hover:bg-primary-container rounded-lg px-4 py-2 transition-all transform hover:scale-105 pull-right" wire:click="showformadd" type="button">{{ trans('Parent_trans.add_parent') }}</button><br><br>
<div class="table-responsive rounded-lg">
    <table id="datatable" class="table table-hover rounded-lg" data-page-length="50"
           style="text-align: center">
        <thead>
        <tr class="bg-surface-container">
            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tl-lg">#</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.Email') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.Name_Father') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.National_ID_Father') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.Passport_ID_Father') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.Phone_Father') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant">{{ trans('Parent_trans.Job_Father') }}</th>
            <th class="px-4 py-3 text-label-lg text-on-surface-variant rounded-tr-lg">{{ trans('Parent_trans.Processes') }}</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
        <?php $i = 0; ?>
        @foreach ($my_parents as $my_parent)
            <tr class="hover:bg-surface-container-high transition-colors">
                <?php $i++; ?>
                <td class="px-4 py-4">
                    <div class="w-8 h-8 rounded-full bg-primary-container flex items-center justify-center text-primary text-xs mx-auto">
                        {{ $i }}
                    </div>
                </td>
                <td class="px-4 py-4">
                    <span class="bg-tertiary/15 text-tertiary px-2 py-1 rounded text-xs">
                        {{ $my_parent->Email }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <div class="w-8 h-8 rounded-full bg-secondary-container flex items-center justify-center text-secondary text-xs">
                            {{mb_substr($my_parent->Name_Father, 0, 2)}}
                        </div>
                        <span class="text-body-md">{{$my_parent->Name_Father}}</span>
                    </div>
                </td>
                <td class="px-4 py-4">
                    <span class="bg-warning/15 text-warning px-2 py-1 rounded text-xs">
                        {{ $my_parent->National_ID_Father }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    <span class="bg-success/15 text-success px-2 py-1 rounded text-xs">
                        {{ $my_parent->Passport_ID_Father }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    <span class="bg-error/15 text-error px-2 py-1 rounded text-xs">
                        {{ $my_parent->Phone_Father }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    <span class="bg-primary/15 text-primary px-2 py-1 rounded text-xs">
                        {{ $my_parent->Job_Father }}
                    </span>
                </td>
                <td class="px-4 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button wire:click="edit({{ $my_parent->id }})" title="{{ trans('Grades_trans.Edit') }}"
                                class="btn bg-tertiary text-on-tertiary hover:bg-tertiary-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105"><i class="fa fa-edit"></i></button>
                        <button type="button" class="btn bg-error text-on-error hover:bg-error-container rounded-lg px-3 py-2 text-sm transition-all transform hover:scale-105" wire:click="delete({{ $my_parent->id }})" title="{{ trans('Grades_trans.Delete') }}"><i class="fa fa-trash"></i></button>
                    </div>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@push('styles')
<style>
/* Global border radius for all divs */
div {
    border-radius: 8px;
}

/* Specific element border radius */
.table {
    border-radius: 12px !important;
    overflow: hidden;
}

.table-responsive {
    border-radius: 12px !important;
}

.btn {
    border-radius: 8px !important;
}

/* Enhanced styling */
button {
    border-radius: 8px;
}

/* Smooth transitions */
div, .table, .btn, button {
    transition: border-radius 0.3s ease, transform 0.2s ease;
}

/* Hover effects */
.btn:hover {
    transform: translateY(-2px);
}

button:hover {
    transform: translateY(-2px);
}

.table tbody tr:hover {
    transform: scale(1.01);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    div {
        border-radius: 6px;
    }
    
    .table {
        border-radius: 8px !important;
    }
    
    .btn {
        border-radius: 6px !important;
    }
    
    button {
        border-radius: 6px;
    }
}
</style>
@endpush
