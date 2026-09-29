<?php

namespace App\Http\Resources;

use App\Models\LoanAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin LoanAttachment
 */
class LoanAttachmentResource extends JsonResource
{
    /**
     * The public disk is served from the host the app itself was reached on, so
     * the URL stays valid when the mobile app talks to the API by LAN address.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => $this->disk === 'public'
                ? $request->getSchemeAndHttpHost().'/storage/'.ltrim($this->path, '/')
                : $this->url,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'size_bytes' => $this->size_bytes,
        ];
    }
}
