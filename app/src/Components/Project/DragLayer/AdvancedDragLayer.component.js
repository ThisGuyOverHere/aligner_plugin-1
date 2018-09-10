import * as React from 'react'
import {DragLayer, XYCoord} from 'react-dnd'
import {ItemTypes} from '../../../Constants/Draggable.constants';
import SegmentDragLayer from './SegmentDragLayer.component';
import keydown from 'react-keydown';
import {Component} from "react";
import ProjectActions from "../../../Actions/Project.actions";
import SystemActions from "../../../Actions/System.actions";


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
        document.addEventListener("keydown", this.setMergeStatus, false);
        document.addEventListener("keyup", this.unsetMergeStatus, false);

    }
    componentWillUnmount(){
        document.removeEventListener("keydown", this.setMergeStatus, false);
        document.removeEventListener("keyup", this.unsetMergeStatus, false);
        ProjectActions.setMergeStatus(false);
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
                {/*<p className={mergeClass.join(" ")}>CMD for Merge</p>*/}
            </div>
        )
    };

    setMergeStatus = (event) =>{
        if(event.key === 'Alt'){
            ProjectActions.setMergeStatus(true);
            this.setState({
                merge: true
            });
        }

    };
    unsetMergeStatus = (event) =>{
        if(event.key === 'Alt'){
            ProjectActions.setMergeStatus(false);
            this.setState({
                merge: false
            });
        }
    }
}

export default DragLayer(monitor => ({
    item: monitor.getItem(),
    itemType: monitor.getItemType(),
    initialOffset: monitor.getInitialSourceClientOffset(),
    currentOffset: monitor.getSourceClientOffset(),
    isDragging: monitor.isDragging(),
}))(AdvancedDragLayer)
