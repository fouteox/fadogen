import { usePage } from '@inertiajs/react';
import React from 'react';

interface VideoInfo {
    streamUrl?: string;
    mimeType?: string;
}

interface SimpleVideoPlayerProps {
    className?: string;
}

const SimpleVideoPlayer: React.FC<SimpleVideoPlayerProps> = ({
    className = '',
}) => {
    const { props } = usePage();
    const videoInfo = props.videoPlayer as VideoInfo;

    return (
        <div
            className={`relative aspect-video w-full overflow-hidden rounded-lg ${className}`}
        >
            <video
                className="h-full w-full rounded-lg"
                preload="metadata"
                controls
            >
                <source src={videoInfo.streamUrl} type={videoInfo.mimeType} />
            </video>
        </div>
    );
};

export default SimpleVideoPlayer;
