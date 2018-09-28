import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";
import ProjectStore from "../../../../Stores/Project.store";
import ProjectConstants from "../../../../Constants/Project.constants";
import SystemConstants from "../../../../Constants/System.constants";
import SystemStore from "../../../../Stores/System.store";

class ActionsBetweenLines extends Component {
    static propTypes = {
        row: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            isHover: false,
            inDrag: false
        };
    }


    componentDidMount() {
        SystemStore.addListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    componentWillUnmount() {
        SystemStore.removeListener(SystemConstants.IN_DRAG, this.dragStatus);
    }

    render() {
        const {row} = this.props;
        let sourceClasses = ['action'];
        let targetClasses = ['action'];
        if (row.target.content_clean && row.source.content_clean) {
            return (
                <div className="actions-between-lines"
                     style={this.getStyle()}
                >
                    <div className={sourceClasses.join(" ")}>
                        {row.target.content_clean &&
                        <div className="action-content" onClick={this.sourceClick}><span></span></div>}
                    </div>
                    <div className={targetClasses.join(" ")}>
                        {this.props.row.source.content_clean &&
                        <div className="action-content" onClick={this.targetClick}><span></span></div>}
                    </div>

                </div>
            )
        }else{
            return null
        }

    }

    getStyle = () => {
        if (this.state.inDrag) {
            return {
                opacity: 0
            }
        }

        return {}

    };

    sourceClick = () => {
        if (this.props.row.source.content_clean) {
            ProjectActions.createSpaceSegment({
                order: this.props.row.source.order,
                type: 'source'
            });
            this.setState({
                isHover: false
            });
        }
    };
    targetClick = () => {
        if (this.props.row.target.content_clean) {
            ProjectActions.createSpaceSegment({
                order: this.props.row.target.order,
                type: 'target'
            });
            this.setState({
                isHover: false
            });
        }
    };

    handleMouseHoverEnter = () => {
        this.setState({
            isHover: true
        });
    };
    handleMouseHoverLeave = () => {
        this.setState({
            isHover: false
        });
    };

    dragStatus = (status) => {
        this.setState({
            inDrag: status
        });
    };
}

export default ActionsBetweenLines;