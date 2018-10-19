import * as React from 'react'
import {DragLayer, XYCoord} from 'react-dnd'
import {ItemTypes} from '../../../Constants/Draggable.constants';
import SegmentDragLayer from './SegmentDragLayer.component';
import {Component} from "react";


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


class AdvancedDragLayer extends Component {

    constructor(props) {
        super(props)
        this.state = {
            merge: false
        }
    }

    renderItem = () => {
        switch (this.props.itemType) {
            case ItemTypes.ITEM:
                return <SegmentDragLayer item={this.props.item}/>;
            default:
                return null
        }
    };

    componentDidMount(){

    }
    componentWillUnmount(){
    }

    render = () => {
        const {isDragging} = this.props;
        let mergeClass = ['merge-text'];
        if (!isDragging) {
            return null
        }
        if(this.state.merge){
            mergeClass.push('active')
        }

        return (
            <div style={layerStyles}>
                <div className="ui container" ref={(el) => {
                    if (el) {
                        el.style.setProperty('margin', '0px', 'important');
                    }
                }}>
                    <div className="grid-dropLayer" style={getItemStyles(this.props)}>{this.renderItem()}</div>
                </div>
            </div>
        )
    };
}

export default DragLayer(monitor => ({
    item: monitor.getItem(),
    itemType: monitor.getItemType(),
    initialOffset: monitor.getInitialSourceClientOffset(),
    currentOffset: monitor.getSourceClientOffset(),
    isDragging: monitor.isDragging(),
}))(AdvancedDragLayer)
