import * as React from 'react'
import {DragLayer, XYCoord} from 'react-dnd'
import {ItemTypes} from '../../../Constants/Draggable.constants';
import SegmentDragLayer from './SegmentDragLayer.component';


const layerStylesContainer = {
    position: 'relative',
    padding: '1rem',
    width: '15.75%',
    display: 'inline-block'
};

const layerStyles = {
    position: 'fixed',
    pointerEvents: 'none',
    zIndex: 9999,
    left: 0,
    top: 0,
    width: '100%',
    height: '100%',
};

function getItemStyles(props) {
    const {initialOffset, currentOffset} = props
    if (!initialOffset || !currentOffset) {
        return {
            display: 'none',
        }
    }

    let {x, y} = currentOffset;

    const transform = `translate(${x}px, ${y}px)`
    return {
        transform,
        WebkitTransform: transform,
    }
}


const AdvancedDragLayer = props => {
    const {item, itemType, isDragging} = props;

    function renderItem() {
        switch (itemType) {
            case ItemTypes.ITEM:
                return <SegmentDragLayer item={item}/>;
            default:
                return null
        }
    }

    if (!isDragging) {
        return null
    }
    return (
        <div style={layerStylesContainer}>
            <div style={layerStyles}>
                <div style={getItemStyles(props)}>{renderItem()}</div>
            </div>
        </div>
    )
}

export default DragLayer(monitor => ({
    item: monitor.getItem(),
    itemType: monitor.getItemType(),
    initialOffset: monitor.getInitialSourceClientOffset(),
    currentOffset: monitor.getSourceClientOffset(),
    isDragging: monitor.isDragging(),
}))(AdvancedDragLayer)
