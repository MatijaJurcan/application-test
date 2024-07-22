'use client';

import { useQuery } from "@apollo/client";
import { Card, List, Alert, Skeleton } from "antd";
import scheduleQuery from "./queries/scheduleQuery";
import dayjs from "dayjs";
import { SubsetInterface } from "../../../types/DataModelTypes/SubsetInterface";
import { EventInterface } from "../../../types/DataModelTypes/EventInterface";
import Link from "next/link";

export default function Schedule() {

    const { data, loading, error } = useQuery<{ eventsSubset: SubsetInterface<EventInterface> }>(
        scheduleQuery,
        {
            fetchPolicy: "network-only",
            nextFetchPolicy: "cache-first",
            variables: {
                limit: 10,
                offset: 0,
                startDate: dayjs().startOf("day").toISOString()
            }
        }
    );
    if (loading) {
        return <Skeleton loading active />;
    }

    if (error) {
        return <Alert message="Loading failed succesfully" description="There was an error loading the schedule. Instead of loading, tried failing at loading. Failed successfully." type="error" showIcon />;
    }

    return (
        <Card>
            <List
                dataSource={data?.eventsSubset.items ?? []}
                renderItem={event => (
                    <List.Item
                        key={event.id}
                        actions={[<Link href={`/events/${encodeURIComponent(event.id)}`}>Detail</Link>]}
                    >
                        <List.Item.Meta
                            title={<>{dayjs(event.date).format('L')} - {event.name}</>}
                            description={event.program?.map(speech => speech.speaker).join(", ")}
                        />
                    </List.Item>
                )}
            />
        </Card>
    );
}