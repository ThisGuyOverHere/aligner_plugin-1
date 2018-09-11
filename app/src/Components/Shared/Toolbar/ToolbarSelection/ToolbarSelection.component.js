import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../Actions/Project.actions";

class ToolbarSelectionComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {

        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        return (
            <div className="record-count">
                <ul>
                    <li>
                        <i className="icon close" onClick={this.removeAllSection}>&nbsp;</i>
                    </li>
                    <li>
                        <p>
                            <span className="label"> {this.props.selection.count} </span>
                            record selected
                        </p>
                    </li>
                </ul>
            </div>
        );
    }

    removeAllSection = () =>{
        ProjectActions.addSegmentToSelection(-1)
    };

}

export default ToolbarSelectionComponent;