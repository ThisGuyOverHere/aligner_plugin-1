import * as React from 'react'
import {DragLayer, XYCoord} from 'react-dnd'
import {ItemTypes} from '../../../Constants/Draggable.constants';
import SegmentDragLayer from './SegmentDragLayer.component';

const layerStyles = {
    position: 'fixed',
    pointerEvents: 'none',
    zIndex: 9999,
    left: 0,
    top: 0,
    width: '100%',
    height: '100%',
};
const containerStyle = {
    marginLeft: {value:'0px',important:true},
    marginRight: {value:'0px',important:true}
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
        <div style={layerStyles}>
            <div className="ui container" ref={(el) => {
                if (el) {
                    el.style.setProperty('margin', '0px', 'important');
                }
            }}>
                <div className="ui grid middle aligned">
                    <div className="fifteen wide column">
                        <div className="ui grid top aligned">
                            <div className="eight wide column">
                                <div style={getItemStyles(props)}>{renderItem()}</div>
                            </div>
                        </div>
                    </div>
                </div>
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
