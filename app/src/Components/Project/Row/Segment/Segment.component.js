import React, {Component} from 'react';
import {ItemTypes} from '../../../../Constants/Draggable.constants';
import {DragSource, ConnectDragPreview} from 'react-dnd';
import {getEmptyImage} from 'react-dnd-html5-backend';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";

const ItemSource = {
    beginDrag(props) {

        return props;
    },
    canDrag(props, monitor) {
        return props.segment.content_clean && props.enableDrag;
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
        mergeStatus: PropTypes.bool,
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

    getStyles = (props) => {
        const {left, top, isDragging} = props;
        const transform = `translate3d(${left}px, ${top}px, 0)`;
        return {
            transform,
            WebkitTransform: transform,
            // IE fallback: hide the real node using CSS when dragging
            // because IE will ignore our custom "empty image" drag preview.
            cursor: 'default'
        }
    };

    createSpaceSegment = () => {
        ProjectActions.createSpaceSegment({
            order: this.props.segment.order,
            type: this.props.type
        });
    };


    render = () => {
        const {connectDragSource, isDragging, segment, dropHover} = this.props;

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
        return connectDragSource(
            <div className={segmentClasses.join(' ')} style={this.getStyles(this.props)}
                 onClick={this.toggleSelectedSegment}
                 onDoubleClick={this.createSpaceSegment}>
                <div className="segmentBox-content">
                    <p>{segment.content_clean}</p>
                    {this.props.mergeStatus && <span className="merge">
                    MERGE
                </span>}
                </div>

            </div>
        );
    };

    toggleSelectedSegment = () => {
        if(this.props.segment.content_clean){
            ProjectActions.addSegmentToSelection(this.props.segment.order, this.props.type)
        }
    };

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
}

export default DragSource(ItemTypes.ITEM, ItemSource, collect)(SegmentComponent);