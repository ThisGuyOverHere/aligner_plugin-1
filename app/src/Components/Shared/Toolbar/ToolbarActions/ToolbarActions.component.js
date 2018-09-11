import React, {Component} from 'react';
import ProjectActions from "../../../../Actions/Project.actions";
import PropTypes from "prop-types";
import {getSegmentByOrder} from "../../../../Helpers/SegmentUtils.helper";

class ToolbarActionsComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {};
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        let mergeDisabled = false;
        let mergeClasses = ['icon', 'random'];
        //check status of merge action
        if (
            !((this.props.selection.source.count === 0 && this.props.selection.target.count > 1)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count > 1))
        ) {
            mergeDisabled = true;
            mergeClasses.push('disabled');
        }


        return (
            <div className="segment-actions">
                <ul>
                    <li>
                        <i
                            className={"icon object ungroup outline"}
                        >
                        </i>
                    </li>
                    <li>
                        <button
                            disabled={mergeDisabled}
                            onClick={this.onMergeClick}>
                            <i className={mergeClasses.join(" ")}></i>
                        </button>
                    </li>
                    <li>
                        <i
                            className={"icon pin"}
                        >
                        </i>
                    </li>
                    <li>
                        <i
                            className={"icon eye"}
                        >
                        </i>
                    </li>
                </ul>
            </div>
        );
    }

    onMergeClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        let targets = [];
        let source = {};
        this.props.selection[type].list.reverse().map((e, index) => {
            const segment = getSegmentByOrder(this.props.selection[type].list[index], type);
            if (index > 0) {
                targets.push(segment)
            }else{
                source = segment;
            }
        });
        ProjectActions.mergeSegments(targets,source);
        ProjectActions.addSegmentToSelection(-1);
    };


}

export default ToolbarActionsComponent;