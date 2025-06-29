<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stores extends Model
{
    use HasFactory;

    protected $table = 'stores';

    protected $fillable = [
        'name',
        'owner_id',
        'domain_name',
        'store_category',
        'registration_date',
        'expiration_date',
        'is_active',
        'group_id',
        'website_id',
        'sort_order',
        'code',
        'website_code',



    ];
    /**
     * @var mixed
     */
    private $store_name;
    /**
     * @var mixed
     */
    private $owner_id;
    /**
     * @var mixed
     */

}
