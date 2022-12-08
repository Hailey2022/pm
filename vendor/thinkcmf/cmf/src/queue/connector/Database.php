<?php












namespace cmf\queue\connector;

use think\queue\connector\Database as DataBaseConnector;

class Database extends DataBaseConnector
{
    protected $options = [
        'expire'  => 60,
        'default' => 'default',
        'table'   => 'queue_jobs',
        'dsn'     => []
    ];

    
    protected function pushToDatabase($delay, $queue, $payload, $attempts = 0)
    {
        return $this->db->name($this->options['table'])->insert([
            'queue'          => $this->getQueue($queue),
            'payload'        => $payload,
            'attempts'       => $attempts,
            'reserved'       => 0,
            'reserve_time'   => null,
            'available_time' => time() + $delay,
            'create_time'    => time()
        ]);
    }

    
    protected function getNextAvailableJob($queue)
    {
        $this->db->startTrans();

        $job = $this->db->name($this->options['table'])
            ->lock(true)
            ->where('queue', $this->getQueue($queue))
            ->where('reserved', 0)
            ->where('available_time', '<=', time())
            ->order('id', 'asc')
            ->find();

        return $job ? (object)$job : null;
    }

    
    protected function markJobAsReserved($id)
    {
        $this->db->name($this->options['table'])->where('id', $id)->update([
            'reserved'     => 1,
            'reserve_time' => time()
        ]);
    }

    
    protected function releaseJobsThatHaveBeenReservedTooLong($queue)
    {
        $expired = time() - $this->options['expire'];

        $this->db->name($this->options['table'])
            ->where('queue', $this->getQueue($queue))
            ->where('reserved', 1)
            ->where('reserve_time', '<=', $expired)
            ->update([
                'reserved'     => 0,
                'reserve_time' => null,
                'attempts'     => $this->db->raw('attempts + 1')
            ]);
    }


}
