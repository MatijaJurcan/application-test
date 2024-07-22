// frontend/src/components/Program/Program.tsx

'use client';

import React from 'react';
import { List } from 'antd';
import { SpeechInterface } from '../../../../../types/DataModelTypes/SpeechInterface';
import Utility from '../../../../../lib/Utility';

interface ProgramProps {
    program: SpeechInterface[];
}

export default function Program({ program }: ProgramProps) {
    return (
        <List
            itemLayout="horizontal"
            dataSource={program}
            renderItem={(speech: SpeechInterface) => (
                <List.Item>
                    <List.Item.Meta
                        title={speech.topic || 'N/A'}
                        description={
                            <>
                                <div>Speaker: {speech.speaker || 'N/A'}</div>
                                <div>
                                    Time: {speech.startTime !== undefined && speech.endTime !== undefined
                                        ? `${Utility.formatTime(speech.startTime)} - ${Utility.formatTime(speech.endTime)}`
                                        : 'N/A'}
                                </div>
                            </>
                        }
                    />
                </List.Item>
            )}
        />
    );
}