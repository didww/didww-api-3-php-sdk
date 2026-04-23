<?php

namespace Didww\Item\OrderItem;

class Emergency extends Base
{
    use Traits\Qty;

    protected function getCreatableAttributesKeys()
    {
        return ['qty', 'emergency_calling_service_id'];
    }

    public function getType(): string
    {
        return 'emergency_order_items';
    }

    public function getEmergencyCallingServiceId(): string
    {
        return $this->getAttributes()['emergency_calling_service_id'];
    }

    public function setEmergencyCallingServiceId(string $emergencyCallingServiceId)
    {
        $this->attributes['emergency_calling_service_id'] = $emergencyCallingServiceId;
    }

    public function setEmergencyCallingService(\Didww\Item\EmergencyCallingService $emergencyCallingService)
    {
        $this->attributes['emergency_calling_service_id'] = $emergencyCallingService->getId();
    }
}
