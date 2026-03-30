<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 【重要】注释掉你错误写入的模型代码（保留迁移文件原有结构）
/*
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'content',
        'is_public'
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->onDelete('cascade');
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->onDelete('cascade');
    }
}
*/

return new class extends Migration
{
    /**
     * 执行迁移：创建notes表
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id(); // 主键id（自增）
            // 关联users表，外键user_id，用户删除时笔记也删除
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            // 关联categories表，外键category_id，分类删除时笔记也删除
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('title'); // 笔记标题（字符串，非空）
            $table->text('content'); // 笔记内容（长文本）
            $table->tinyInteger('is_public')->default(0); // 公开状态：0私有/1公共，默认私有
            $table->timestamps(); // 自动维护created_at/updated_at时间戳
        });
    }

    /**
     * 回滚迁移：删除notes表
     */
    public function down()
    {
        Schema::dropIfExists('notes');
    }
};