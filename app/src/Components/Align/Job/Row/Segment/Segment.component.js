import React, {Component} from 'react';
import {ItemTypes} from '../../../../../Constants/Draggable.constants';
import {DragSource, DropTarget} from 'react-dnd';
import {getEmptyImage} from 'react-dnd-html5-backend';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";

const ItemSource = {
    beginDrag(props) {
        ProjectActions.addSegmentToSelection(-1);
        return props;
    },
    canDrag(props, monitor) {
        return props.segment.content_clean;
    },
    endDrag(props, monitor, component) {

    }
};

function collect(connect, monitor) {
    return {
        connectDragPreview: connect.dragPreview(),
        connectDragSource: connect.dragSource(),
        isDragging: monitor.isDragging(),
        canDrag: monitor.canDrag()
    }
}

class SegmentComponent extends Component {
    static propTypes = {
        type: PropTypes.string.isRequired,
        dropHover: PropTypes.bool.isRequired,
        selected: PropTypes.bool,
        enableDrag: PropTypes.bool,
        segment: PropTypes.shape({
            order: PropTypes.number.isRequired,
            content_clean: PropTypes.oneOfType([() => {
                return null
            }, PropTypes.number]).isDefined,
            next: PropTypes.oneOfType([() => {
                return null
            }, PropTypes.number]).isDefined
        }).isRequired

    };

    constructor(props) {
        super(props);
        this.state = {};
    }

    static getDerivedStateFromProps(props, state) {

        return null;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {

    }

    componentDidMount() {
        const {connectDragPreview} = this.props;
        if (connectDragPreview) {
            // Use empty image as a drag preview so browsers don't draw it
            // and we can draw whatever we want on the custom drag layer instead.
            connectDragPreview(getEmptyImage(), {
                // IE fallback: specify that we'd rather screenshot the node
                // when it already knows it's being dragged so we can hide it with CSS.
                captureDraggingState: true,
            })
        }
    }

    componentWillUnmount() {
    }


    render = () => {
        const {connectDragPreview, connectDragSource, isDragging, segment, dropHover, isOver, dragEl} = this.props;

        let segmentClasses = ['segmentBox'];
        if (!segment.content_clean) {
            segmentClasses.push('empty')
        }
        if (isDragging) {
            segmentClasses.push('onDrag')
        }
        if (dropHover) {
            segmentClasses.push('onDropHover')
        }
        if (this.props.selected) {
            segmentClasses.push('selected')
        }

        if(isOver && dragEl.type === this.props.segment.type){
            segmentClasses.push('onDropHoverMerge')
        }
        return connectDragPreview(connectDragSource(
            <div className={segmentClasses.join(' ')} style={this.getStyles(this.props)}
                 onDoubleClick={this.openSplitModal}
                 onClick={this.handleClick}
            >
                {dropHover && <span className="dropAlignArea"> </span>}
                <i className="icon check circle outline"></i>
                <div className="segmentBox-content">
                    <p>
                        {segment.content_clean}
                    </p>
                    {false && !segment.content_clean && !dragEl && <i className="trash alternate outline icon"> </i>}

                </div>

            </div>
        ));
    };

    handleClick = () =>{
        if (this.props.segment.content_clean) {
            ProjectActions.addSegmentToSelection(this.props.segment.order, this.props.type)
        }else{
           /* ProjectActions.removeSpaceSegment({
                order: this.props.segment.order,
                type: this.props.segment.type
            });*/
        }
    };


    openSplitModal = () => {
        if(this.props.segment.content_clean){
            ProjectActions.openSegmentToSplit(this.props.segment);
        }

    };


    getStyles = (props) => {
        const {left, top} = props;
        const transform = `translate3d(${left}px, ${top}px, 0)`;
        return {
            transform,
            WebkitTransform: transform,
            // IE fallback: hide the real node using CSS when dragging
            // because IE will ignore our custom "empty image" drag preview.
        }
    };

}

export default DragSource(ItemTypes.ITEM, ItemSource, collect)(SegmentComponent);
