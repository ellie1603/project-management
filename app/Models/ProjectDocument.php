<?php

namespace App\Models;

use App\Models\Concerns\RestrictedToAssignedProjects;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocument extends Model
{
    use HasFactory, RestrictedToAssignedProjects;

    /** Only raster images are safe to display inline; SVG can carry scripts. */
    public const INLINE_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    protected $table = 'project_documents';

    protected $fillable = [
        'project_id',
        'progress_id',
        'document_type',
        'document_name',
        'file_path',
        'file_type',
        'file_size',
        'version',
        'is_current',
        'description',
        'uploaded_by',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by')->withTrashed();
    }

    /** Whether the browser can show this file in the photo viewer. */
    public function isInlineImage(): bool
    {
        return in_array($this->file_type, self::INLINE_IMAGE_TYPES, true);
    }

    /**
     * The entry the shared photo viewer expects: inline source, caption, and
     * a link to download the original.
     *
     * @return array{src: string, caption: string, download: string}
     */
    public function toViewerPhoto(string $caption): array
    {
        return [
            'src' => route('projects.documents.download', [$this->project_id, $this->id, 'inline' => 1]),
            'caption' => $caption,
            'download' => route('projects.documents.download', [$this->project_id, $this->id]),
        ];
    }
}
