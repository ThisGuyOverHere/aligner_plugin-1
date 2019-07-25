import React, {Component} from 'react';
import {ItemTypes} from '../../../../../Constants/Draggable.constants';
import {DragSource} from 'react-dnd';
import {getEmptyImage} from 'react-dnd-html5-backend';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import SegmentContentComponent from "./SegmentContent/SegmentContent.component";
import ProjectStore from "../../../../../Stores/Project.store";
import ProjectConstants from "../../../../../Constants/Project.constants";

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
		rtl: PropTypes.bool,
		type: PropTypes.string.isRequired,
		dropHover: PropTypes.bool.isRequired,
		selected: PropTypes.bool,
		enableDrag: PropTypes.bool,
		search: PropTypes.object,
		segment: PropTypes.shape({
			order: PropTypes.number.isRequired,
			content_clean: PropTypes.oneOfType([() => {
				return null
			}, PropTypes.number]).isDefined,
			next: PropTypes.oneOfType([() => {
				return null
			}, PropTypes.number]).isDefined
		}).isRequired,
		isInMisalignedNavigator: PropTypes.bool,
		selectedInNavigator: PropTypes.bool

	};

	constructor(props) {
		super(props);
		this.state = {
			highlight: false
		};
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

		ProjectStore.addListener(ProjectConstants.SEGMENT_HIGHLIGHT, this.setHighlight);
	}

	componentWillUnmount() {
		ProjectStore.removeListener(ProjectConstants.SEGMENT_HIGHLIGHT, this.setHighlight);
	}


	render = () => {
		const {connectDragPreview, connectDragSource, isDragging, segment, dropHover, isOver, dragEl, rtl, isInMisalignedNavigator, selectedInNavigator} = this.props;
		const {highlight} = this.state;

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

		if ((!segment.content_clean && isInMisalignedNavigator) || highlight) {
			segmentClasses.push('isIn')
		}
		if (!segment.content_clean && selectedInNavigator) {
			segmentClasses.push('isIn-and-selected')
		}
		if (isOver && dragEl.type === this.props.segment.type) {
			segmentClasses.push('onDropHoverMerge')
		}
		return connectDragPreview(connectDragSource(
			<div className={segmentClasses.join(' ')} style={this.getStyles(this.props)}
				 onDoubleClick={this.openSplitModal}
				 onClick={this.handleClick}
			>
				{dropHover && <span className="dropAlignArea"> </span>}
				<i className="icon check circle outline"></i>
				<div className="segmentBox-content"
					 style={{textAlign: rtl ? 'right' : 'left', direction: rtl ? 'rtl' : 'ltr'}}>
					<SegmentContentComponent search={this.props.search} content={segment.content_clean}
											 id={segment.id}/>
				</div>

			</div>
		));
	};

	handleClick = () => {
		if (this.props.segment.content_clean) {
			ProjectActions.addSegmentToSelection(this.props.segment.order, this.props.type)
		} else {
			/* ProjectActions.removeSpaceSegment({
				 order: this.props.segment.order,
				 type: this.props.segment.type
			 });*/
		}
	};


	openSplitModal = () => {
		if (this.props.segment.content_clean) {
			let segment = this.props.segment;
			segment.rtl = this.props.rtl;
			ProjectActions.openSegmentToSplit(segment);
		}

	};

	setHighlight = (segments) => {
		const {segment: {order, type}} = this.props;
		if(segments.filter(e=> e.type === type && e.order === order).length){
			console.log(order,type)
			this.setState({
				highlight: true
			})

			setTimeout(()=>{
				this.setState({
					highlight: false
				})
			},1000)
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
