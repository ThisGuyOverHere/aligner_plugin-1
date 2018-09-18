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
        if (!this.props.row.source.content_clean) {
            sourceClasses.push('up')
        }
        if (!this.props.row.target.content_clean) {
            targetClasses.push('up')
        }

        return (
            <div className="actions-between-lines"
                 style={this.getStyle()}
                 onMouseEnter={this.handleMouseHover}
                 onMouseLeave={this.handleMouseHover}
            >
                <div className={sourceClasses.join(" ")}>
                    {this.props.row.target.content_clean && <div className="action-content" onClick={this.sourceClick}><span><i></i></span></div>}
                </div>
                <div className={targetClasses.join(" ")}>
                    {this.props.row.source.content_clean && <div className="action-content" onClick={this.targetClick}><span><i></i></span></div>}
                </div>

            </div>
        );
    }

    getStyle = () => {
        if (this.state.isHover && !this.state.inDrag) {
            return {
                opacity: 1
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
        } else {
            ProjectActions.removeSpaceSegment({
                order: this.props.row.source.order,
                type: 'source'
            })
        }

    };
    targetClick = () => {
        if (this.props.row.target.content_clean) {
            ProjectActions.createSpaceSegment({
                order: this.props.row.target.order,
                type: 'target'
            });
        } else {
            ProjectActions.removeSpaceSegment({
                order: this.props.row.target.order,
                type: 'target'
            })
        }

    };

    handleMouseHover = () => {
        this.setState({
            isHover: !this.state.isHover
        });
    };
    dragStatus = (status) => {
        this.setState({
            inDrag: status
        });
    };
}

export default ActionsBetweenLines;