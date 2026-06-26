<?php

namespace Meraki\Core\Contracts;

use Meraki\Core\Hub\HubPluginInfo;
use Meraki\Core\Hub\HubUpdateInfo;

interface HubClientInterface
{
    /** Kiểm tra kết nối tới hub. Trả false nếu hub không phản hồi. */
    public function ping(): bool;

    /** Lấy thông tin plugin theo hubId. Ném HubException nếu không tìm thấy hoặc lỗi mạng. */
    public function getPlugin(string $hubId): HubPluginInfo;

    /**
     * Kiểm tra bản cập nhật cho một plugin.
     * Trả null nếu đang dùng phiên bản mới nhất.
     */
    public function checkUpdate(string $hubId, string $currentVersion): ?HubUpdateInfo;

    /**
     * Kiểm tra batch nhiều plugin cùng lúc.
     *
     * @param  array<string, string>  $plugins  [hubId => currentVersion]
     * @return array<string, HubUpdateInfo|null>  [hubId => update info hoặc null]
     */
    public function checkUpdates(array $plugins): array;
}
