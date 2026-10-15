<?php

return [
    'success'=>'Data has been saved successfully',
    'Update'=>'Data has been Updated successfully',
    'Delete'=>'Data has been Deleted successfully',
    'cannot_update_paid_plan'=>'Cannot update plan because some installments have already been paid.',
    'cannot_delete_paid_plan'=>'Cannot delete plan because some installments have already been paid.',
    'invalid_student_installment'=>'Invalid student installment for this student.',
    'installment_sum_mismatch'=>'The sum of installments must equal the total amount.',
    'installment_dates_not_sequential'=>'Installment due dates should be in ascending order.',
    'teacher'=>'Teacher',
    'staff'=>'Staff',
    'salary_payment_description'=>'Salary payment for :name - :month/:year',
    'work_hours_only_hourly'=>'Work hours can only be logged for hourly-contract teachers.',
    'work_hours_invalid_window'=>'The from/to window must produce between 0.01 and 24 hours.',
    'work_hours_conflict'=>'Time conflict: the :start → :end window is already covered by another entry on this day.',
    'work_hours_stored'=>'Work hours logged successfully.',
    'work_hours_deleted'=>'Work hours entry deleted successfully.',
    'work_hours_not_found'=>'Work hours entry not found.',
    'work_hours_retrieved'=>'Work hours retrieved successfully.',
];
