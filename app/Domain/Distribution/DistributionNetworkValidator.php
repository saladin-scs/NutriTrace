<?php

namespace App\Domain\Distribution;

use App\Models\DistributionChannel;
use App\Models\DistributionLink;
use App\Models\DistributionNode;
use InvalidArgumentException;

final class DistributionNetworkValidator
{
    public function assertLinkEndpointsBelongToChannel(DistributionLink $link): void
    {
        $from = $link->fromNode ?? DistributionNode::query()->find($link->from_node_id);
        $to = $link->toNode ?? DistributionNode::query()->find($link->to_node_id);

        if (! $from || ! $to) {
            throw new InvalidArgumentException('Les nœuds du lien doivent exister.');
        }

        if ((int) $from->distribution_channel_id !== (int) $link->distribution_channel_id
            || (int) $to->distribution_channel_id !== (int) $link->distribution_channel_id) {
            throw new InvalidArgumentException('Les nœuds doivent appartenir au même canal de distribution.');
        }

        if ((int) $from->id === (int) $to->id) {
            throw new InvalidArgumentException('Un lien ne peut pas relier un nœud à lui-même.');
        }
    }

    /**
     * BFS shortest path by hop count. Returns node IDs from origin to destination inclusive.
     *
     * @return list<int>
     */
    public function resolvePath(DistributionChannel $channel, int $originNodeId, int $destinationNodeId): array
    {
        if ($originNodeId === $destinationNodeId) {
            return [$originNodeId];
        }

        $adjacency = [];
        $channel->links()
            ->where('status', 'active')
            ->get(['from_node_id', 'to_node_id'])
            ->each(function (DistributionLink $link) use (&$adjacency) {
                $adjacency[$link->from_node_id][] = $link->to_node_id;
            });

        $queue = [[$originNodeId]];
        $visited = [$originNodeId => true];

        while ($queue !== []) {
            $path = array_shift($queue);
            $current = end($path);

            foreach ($adjacency[$current] ?? [] as $next) {
                if (isset($visited[$next])) {
                    continue;
                }

                $nextPath = [...$path, $next];
                if ($next === $destinationNodeId) {
                    return $nextPath;
                }

                $visited[$next] = true;
                $queue[] = $nextPath;
            }
        }

        throw new InvalidArgumentException('Aucun chemin trouvé entre les nœuds sélectionnés sur ce canal.');
    }
}
